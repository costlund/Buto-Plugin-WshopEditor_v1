<?php
/**
 * Editor for products.
 */
class PluginWshopEditor_v1{
  private $settings = null;
  private $sql = null;
  /**
   * 
   * @param type $buto
   */
  function __construct($buto) {
    if($buto){
      wfPlugin::includeonce('wf/yml');
      wfPlugin::includeonce('wf/array');
      wfPlugin::enable('wf/form_v2');
      wfPlugin::enable('datatable/datatable_1_10_16');
      wfPlugin::enable('upload/file');
    }
  }
  /**
   * 
   */
  private function init_page(){
    wfArray::set($GLOBALS, 'sys/layout_path', '/plugin/wshop/editor_v1/layout');
    if(!wfUser::hasRole("webmaster") && !wfUser::hasRole("webadmin")){
      exit('Role webmaster or webadmin is required!');
    }
    $this->settings = new PluginWfArray(wfArray::get($GLOBALS, 'sys/settings/plugin_modules/'.wfArray::get($GLOBALS, 'sys/class').'/settings'));
    $this->settings->set('mysql', wfSettings::getSettingsFromYmlString($this->settings->get('mysql')));
    $this->sql = wfSettings::getSettingsAsObject("/plugin/wshop/editor_v1/mysql/sql.yml");
    if(wfRequest::get('ws_p_id')){
      $this->sql->set('ws_p_id/params/id/value', wfRequest::get('ws_p_id'));
      $rs = $this->executeSQL($this->sql->get('ws_p_id'));
      wfArray::set($GLOBALS, 'sys/settings/plugin/wshop/editor_v1/product', $rs->get('0'));
    }
    if(wfRequest::get('ws_t_id')){
      $this->sql->set('ws_t_id/params/id/value', wfRequest::get('ws_t_id'));
      $rs = $this->executeSQL($this->sql->get('ws_t_id'));
      wfArray::set($GLOBALS, 'sys/settings/plugin/wshop/editor_v1/type', $rs->get('0'));
    }
  }
  /**
   * Start page.
   */
  public function page_start(){
    $this->init_page();
    $page = $this->getYml('page/start.yml');
    wfDocument::mergeLayout($page->get());
  }
  /**
   * 
   */
  public function page_products(){
    $this->init_page();
    $page = $this->getYml('page/products.yml');
    $table = $this->getYml('html_object/table.yml');
    $table->setById('thead_tr', 'innerHTML', array(
      wfDocument::createHtmlElement('th', 'ID'),
      wfDocument::createHtmlElement('th', 'Type'),
      wfDocument::createHtmlElement('th', 'Sort'),
      wfDocument::createHtmlElement('th', 'Name')
    ));
    /**
     * Get from db.
     */
    $this->sql->set('products/params/language/value', wfI18n::getLanguage());
    $rs = $this->executeSQL($this->sql->get('products'));
    $tr = array();
    foreach ($rs->get() as $key => $value) {
      $item = new PluginWfArray($value);
      $tr[] = wfDocument::createHtmlElement('tr', array(
        wfDocument::createHtmlElement('td', $item->get('id')),
        wfDocument::createHtmlElement('td', $item->get('product_type_id')),
        wfDocument::createHtmlElement('td', $item->get('sort')),
        wfDocument::createHtmlElement('td', $item->get('name'))
      ), array('onclick' => "PluginWfBootstrapjs.modal({id: 'modal_product', url: 'product/ws_p_id/".$item->get('id')."', lable: 'Product', size: 'lg'});"));
    }
    $table->setById('tbody', 'innerHTML', $tr);
    $page->setById('content', 'innerHTML', array($table->get()));
    /**
     * 
     */
    wfDocument::mergeLayout($page->get());
  }
  /**
   * Product view.
   */
  public function page_product(){
    $this->init_page();
    $page = $this->getYml('page/product.yml');
    $id = wfArray::get($GLOBALS, 'sys/settings/plugin/wshop/editor_v1/product/id');
    /**
     * Images.
     */
    $div = wfDocument::createHtmlElement('div', null, array('id' => "product_images", 'style' => 'min-height:200px'));
    $page->setById('language', 'innerHTML/', $div);
    $div = wfDocument::createHtmlElement('script', "PluginWfAjax.load('product_images', 'product_images/ws_p_id/$id');");
    $page->setById('language', 'innerHTML/', $div);
    /**
     * Add i18n.
     */
    foreach (wfI18n::getLanguages() as $key => $language) {
      $div = wfDocument::createHtmlElement('div', null, array('id' => "product_i18n_$language", 'style' => 'min-height:200px'));
      $page->setById('language', 'innerHTML/', $div);
      $div = wfDocument::createHtmlElement('script', "PluginWfAjax.load('product_i18n_$language', 'product_i18n/language/$language/ws_p_id/$id');");
      $page->setById('language', 'innerHTML/', $div);
    }
    /**
     * Render.
     */
    wfDocument::mergeLayout($page->get());
  }
  /**
   * 
   */
  public function page_product_i18n(){
    $this->init_page();
    $id = wfArray::get($GLOBALS, 'sys/settings/plugin/wshop/editor_v1/product/id');
    $language = wfRequest::get('language');
    $this->sql->set('product_i18n/params/product_id/value', $id);
    $this->sql->set('product_i18n/params/language/value', $language);
    $rs = $this->executeSQL($this->sql->get('product_i18n'));
    $div = wfDocument::createHtmlElement('div', array(
      wfDocument::createHtmlElement('p', "Language: $language", array('class' => 'list-group-item active', 'onclick' => "PluginWfAjax.update('product_i18n_$language');")),
      wfDocument::createHtmlElement('strong', $rs->get('0/name'), array('class' => 'list-group-item')),
      wfDocument::createHtmlElement('p', $rs->get('0/description'), array('class' => 'list-group-item')),
      wfDocument::createHtmlElement('p', $rs->get('0/description_more'), array('class' => 'list-group-item')),
      wfDocument::createHtmlElement('p', array(wfDocument::createHtmlElement('a', 'Edit', array('class' => 'btn btn-default', 'onclick' => "PluginWfBootstrapjs.modal({id: 'modal_product_i18n_form', url: 'product_i18n_form/ws_p_id/".wfArray::get($GLOBALS, 'sys/settings/plugin/wshop/editor_v1/product/id')."/language/$language', lable: 'Product ($language)'});"))), array('class' => 'list-group-item')),
      ), array('class' => 'list-group'));
    wfDocument::renderElement(array($div));
  }
  /**
   * 
   */
  public function page_type_i18n(){
    $this->init_page();
    $id = wfArray::get($GLOBALS, 'sys/settings/plugin/wshop/editor_v1/type/id');
    $language = wfRequest::get('language');
    $this->sql->set('type_i18n/params/product_type_id/value', $id);
    $this->sql->set('type_i18n/params/language/value', $language);
    $rs = $this->executeSQL($this->sql->get('type_i18n'));
    $div = wfDocument::createHtmlElement('div', array(
      wfDocument::createHtmlElement('p', "Language: $language", array('class' => 'list-group-item active', 'onclick' => "PluginWfAjax.update('type_i18n_$language');")),
      wfDocument::createHtmlElement('strong', $rs->get('0/name'), array('class' => 'list-group-item')),
      wfDocument::createHtmlElement('p', $rs->get('0/description'), array('class' => 'list-group-item')),
      wfDocument::createHtmlElement('p', $rs->get('0/description_more'), array('class' => 'list-group-item')),
      wfDocument::createHtmlElement('p', array(wfDocument::createHtmlElement('a', 'Edit', array('class' => 'btn btn-default', 'onclick' => "PluginWfBootstrapjs.modal({id: 'modal_type_i18n_form', url: 'type_i18n_form/ws_t_id/".wfArray::get($GLOBALS, 'sys/settings/plugin/wshop/editor_v1/type/id')."/language/$language', lable: 'Type ($language)'});"))), array('class' => 'list-group-item')),
      ), array('class' => 'list-group'));
    wfDocument::renderElement(array($div));
  }
  /**
   * 
   */
  public function page_product_images(){
    /**
     * 
     */
    $this->init_page();
    $id = wfArray::get($GLOBALS, 'sys/settings/plugin/wshop/editor_v1/product/id');
    $element = array();
    $element[] = wfDocument::createHtmlElement('h2', 'Images', array('onclick' => "PluginWfAjax.update('product_images');"));
    $data = new PluginWfYml('/plugin/wshop/editor_v1/form/plugin_upload_file.yml', 'file_upload_data');
    $element2 = array();
    /**
     * Image 1.
     */
    $data->set('id', "form_1");
    $data->set('url', 'upload_image_capture?ws_p_id='.$id);
    $data->set('name', "$id.jpg");
    $element2[] = wfDocument::createHtmlElement('div', array(wfDocument::createHtmlElement('div', array(
      wfDocument::createHtmlElement('h2', 'Image 1'),
      wfDocument::createWidget('upload/file', 'element', $data->get())
      ), array('class' => 'well', 'style' => 'min-height:200px'))), array('class' => 'col-sm-3'));
    /**
     * Image 2.
     */
    $data->set('id', "form_2");
    $data->set('url', 'upload_image_capture?ws_p_id='.$id.'&form=1');
    $data->set('name', $id."_1.jpg");
    $element2[] = wfDocument::createHtmlElement('div', array(wfDocument::createHtmlElement('div', array(
      wfDocument::createHtmlElement('h2', 'Image 2'),
      wfDocument::createWidget('upload/file', 'element', $data->get())
      ), array('class' => 'well', 'style' => 'min-height:200px'))), array('class' => 'col-sm-3'));
    /**
     * Image 3.
     */
    $data->set('id', "form_3");
    $data->set('url', 'upload_image_capture?ws_p_id='.$id.'&form=2');
    $data->set('name', $id."_2.jpg");
    $element2[] = wfDocument::createHtmlElement('div', array(wfDocument::createHtmlElement('div', array(
      wfDocument::createHtmlElement('h2', 'Image 3'),
      wfDocument::createWidget('upload/file', 'element', $data->get())
      ), array('class' => 'well', 'style' => 'min-height:200px'))), array('class' => 'col-sm-3'));
    /**
     * Image 4.
     */
    $data->set('id', "form_4");
    $data->set('url', 'upload_image_capture?ws_p_id='.$id.'&form=3');
    $data->set('name', $id."_3.jpg");
    $element2[] = wfDocument::createHtmlElement('div', array(wfDocument::createHtmlElement('div', array(
      wfDocument::createHtmlElement('h2', 'Image 4'),
      wfDocument::createWidget('upload/file', 'element', $data->get())
      ), array('class' => 'well', 'style' => 'min-height:200px'))), array('class' => 'col-sm-3'));
    /**
     * Image 5.
     */
    $data->set('id', "form_5");
    $data->set('url', 'upload_image_capture?ws_p_id='.$id.'&form=4');
    $data->set('name', $id."_4.jpg");
    $element2[] = wfDocument::createHtmlElement('div', array(wfDocument::createHtmlElement('div', array(
      wfDocument::createHtmlElement('h2', 'Image 5'),
      wfDocument::createWidget('upload/file', 'element', $data->get())
      ), array('class' => 'well', 'style' => 'min-height:200px'))), array('class' => 'col-sm-3'));
    /**
     * Render.
     */
    $element[] = wfDocument::createHtmlElement('div', $element2, array('class' => 'row'));
    wfDocument::renderElement($element);
  }
  /**
   * 
   */
  public function page_type_images(){
    /**
     * 
     */
    $this->init_page();
    $id = wfArray::get($GLOBALS, 'sys/settings/plugin/wshop/editor_v1/type/id');
    $element = array();
    $element[] = wfDocument::createHtmlElement('h2', 'Images', array('onclick' => "PluginWfAjax.update('type_images');"));
    $data = new PluginWfYml('/plugin/wshop/editor_v1/form/plugin_upload_file_type_images.yml', 'file_upload_data');
    $element2 = array();
    /**
     * Image 1.
     */
    $data->set('id', "form_1");
    $data->set('url', 'upload_image_capture?ws_t_id='.$id);
    $data->set('name', "$id.jpg");
    $element2[] = wfDocument::createHtmlElement('div', array(wfDocument::createHtmlElement('div', array(
      wfDocument::createHtmlElement('h2', 'Image 1'),
      wfDocument::createWidget('upload/file', 'element', $data->get())
      ), array('class' => 'well', 'style' => 'min-height:200px'))), array('class' => 'col-sm-3'));
    /**
     * Render.
     */
    $element[] = wfDocument::createHtmlElement('div', $element2, array('class' => 'row'));
    wfDocument::renderElement($element);
  }
  public function page_upload_image_capture(){
    $this->init_page();
    if(wfRequest::get('ws_p_id')){
      $id = wfArray::get($GLOBALS, 'sys/settings/plugin/wshop/editor_v1/product/id');
      $element = array();
      $data = new PluginWfYml('/plugin/wshop/editor_v1/form/plugin_upload_file.yml', 'file_upload_data');
      if(wfRequest::get('form')){
        $data->set('name', $id."_".wfRequest::get('form').".jpg");
      }else{
        $data->set('name', "$id.jpg");
      }
      $element[] = wfDocument::createWidget('upload/file', 'capture', $data->get());
      wfDocument::renderElement($element);
    }
    if(wfRequest::get('ws_t_id')){
      $id = wfArray::get($GLOBALS, 'sys/settings/plugin/wshop/editor_v1/type/id');
      $element = array();
      $data = new PluginWfYml('/plugin/wshop/editor_v1/form/plugin_upload_file_type_images.yml', 'file_upload_data');
      $data->set('name', "$id.jpg");
      $element[] = wfDocument::createWidget('upload/file', 'capture', $data->get());
      wfDocument::renderElement($element);
    }
  }
  /**
   * 
   */
  public function page_formupload(){
    $this->init_page();
    $id = wfArray::get($GLOBALS, 'sys/settings/plugin/wshop/editor_v1/product/id');
    $data = new PluginWfYml('/plugin/wshop/editor_v1/form/formupload.yml', 'file_upload_data');
    $data->set('name', "$id.jpg");
    //wfHelp::yml_dump($data, true);
    $element = array();
    $element[] = wfDocument::createWidget('wf/formupload', 'upload', $data->get());
    wfDocument::renderElement($element);
  }
  /**
   * 
   */
  public function page_product_i18n_form(){
    $widget = wfDocument::createWidget('wf/form_v2', 'render', 'yml:/plugin/wshop/editor_v1/form/product_i18n_form.yml');
    wfDocument::renderElement(array($widget));
  }
  /**
   * 
   */
  public function page_type_i18n_form(){
    $widget = wfDocument::createWidget('wf/form_v2', 'render', 'yml:/plugin/wshop/editor_v1/form/type_i18n_form.yml');
    wfDocument::renderElement(array($widget));
  }
  /**
   * 
   */
  public function page_product_i18n_capture(){
    $widget = wfDocument::createWidget('wf/form_v2', 'capture', 'yml:/plugin/wshop/editor_v1/form/product_i18n_form.yml');
    wfDocument::renderElement(array($widget));
  }
  /**
   * 
   */
  public function page_type_i18n_capture(){
    $widget = wfDocument::createWidget('wf/form_v2', 'capture', 'yml:/plugin/wshop/editor_v1/form/type_i18n_form.yml');
    wfDocument::renderElement(array($widget));
  }
  /**
   * 
   */
  public function frm_product_form_render($form){
    $this->init_page();
    if(wfRequest::get('ws_p_id')){
      $form->set('items/ws_p_id/default', wfArray::get($GLOBALS, 'sys/settings/plugin/wshop/editor_v1/product/id'));
      $form->set('items/id/default', wfArray::get($GLOBALS, 'sys/settings/plugin/wshop/editor_v1/product/id'));
      $form->set('items/sort/default', wfArray::get($GLOBALS, 'sys/settings/plugin/wshop/editor_v1/product/sort'));
      $form->set('items/product_type_id/default', wfArray::get($GLOBALS, 'sys/settings/plugin/wshop/editor_v1/product/product_type_id'));
    }
    $this->sql->set('product_type/params/language/value', wfI18n::getLanguage());
    $rs = $this->executeSQL($this->sql->get('product_type'));
    $option = array();
    $option[''] = '';
    foreach ($rs->get() as $key => $value) {
      $option[$value['id']] = $value['id'].' ('.$value['name'].')';
    }
    $form->set('items/product_type_id/option', $option);
    return $form;
  }
  /**
   * 
   */
  public function frm_type_form_render($form){
    $this->init_page();
    if(wfRequest::get('ws_t_id')){
      $form->set('items/ws_t_id/default', wfArray::get($GLOBALS, 'sys/settings/plugin/wshop/editor_v1/type/id'));
      $form->set('items/id/default', wfArray::get($GLOBALS, 'sys/settings/plugin/wshop/editor_v1/type/id'));
      $form->set('items/sort/default', wfArray::get($GLOBALS, 'sys/settings/plugin/wshop/editor_v1/type/sort'));
    }
    return $form;
  }
  /**
   * 
   */
  public function frm_product_i18n_form_render($form){
    $this->init_page();
    $form->set('items/product_id/default', wfArray::get($GLOBALS, 'sys/settings/plugin/wshop/editor_v1/product/id'));
    $form->set('items/language/default', wfRequest::get('language'));
    $this->sql->set('product_i18n/params/product_id/value', wfArray::get($GLOBALS, 'sys/settings/plugin/wshop/editor_v1/product/id'));
    $this->sql->set('product_i18n/params/language/value', wfRequest::get('language'));
    $rs = $this->executeSQL($this->sql->get('product_i18n'));
    $form->set('items/name/default', $rs->get('0/name'));
    $form->set('items/description/default', $rs->get('0/description'));
    $form->set('items/description_more/default', $rs->get('0/description_more'));
    return $form;
  }
  /**
   * 
   */
  public function frm_type_i18n_form_render($form){
    $this->init_page();
    $form->set('items/product_type_id/default', wfArray::get($GLOBALS, 'sys/settings/plugin/wshop/editor_v1/type/id'));
    $form->set('items/language/default', wfRequest::get('language'));
    $this->sql->set('type_i18n/params/product_type_id/value', wfArray::get($GLOBALS, 'sys/settings/plugin/wshop/editor_v1/type/id'));
    $this->sql->set('type_i18n/params/language/value', wfRequest::get('language'));
    $rs = $this->executeSQL($this->sql->get('type_i18n'));
    $form->set('items/name/default', $rs->get('0/name'));
    $form->set('items/description/default', $rs->get('0/description'));
    $form->set('items/description_more/default', $rs->get('0/description_more'));
    return $form;
  }
  /**
   * 
   */
  public function frm_product_form_capture($form){
    $this->init_page();
    if(!wfRequest::get('ws_p_id')){
      /**
       * Create product.
       */
      $this->sql->set('product_insert/params/ws_p_id/value', wfRequest::get('id'));
      $this->executeSQL($this->sql->get('product_insert'));
      /**
       * Set id.
       */
      $this->sql->set('product_update/params/ws_p_id/value', wfRequest::get('id'));
    }else{
      /**
       * Set id.
       */
      $this->sql->set('product_update/params/ws_p_id/value', wfRequest::get('ws_p_id'));
    }
    $this->sql->set('product_update/params/id/value', wfRequest::get('id'));
    $this->sql->set('product_update/params/product_type_id/value', wfRequest::get('product_type_id'));
    $this->sql->set('product_update/params/sort/value', wfRequest::get('sort'));
    $this->executeSQL($this->sql->get('product_update'));
    if(wfRequest::get('ws_p_id') != wfRequest::get('id')){
      return array("PluginWfAjax.update('start_content');$('.modal').modal('hide');");
    }else{
      return array("PluginWfAjax.update('modal_product_body');$('#modal_product_form').modal('hide');");
    }
  }
  /**
   * 
   */
  public function frm_type_form_capture($form){
    $this->init_page();
    if(!wfRequest::get('ws_p_id')){
      /**
       * Create product.
       */
      $this->sql->set('type_insert/params/ws_t_id/value', wfRequest::get('id'));
      $this->executeSQL($this->sql->get('type_insert'));
      /**
       * Set id.
       */
      $this->sql->set('type_update/params/ws_t_id/value', wfRequest::get('id'));
    }else{
      /**
       * Set id.
       */
      $this->sql->set('type_update/params/ws_t_id/value', wfRequest::get('ws_t_id'));
    }
    $this->sql->set('type_update/params/id/value', wfRequest::get('id'));
    $this->sql->set('type_update/params/sort/value', wfRequest::get('sort'));
    $this->executeSQL($this->sql->get('type_update'));
    if(wfRequest::get('ws_t_id') != wfRequest::get('id')){
      return array("PluginWfAjax.update('start_content');$('.modal').modal('hide');");
    }else{
      return array("PluginWfAjax.update('modal_type_body');$('#modal_type_form').modal('hide');");
    }
  }
  /**
   * 
   */
  public function frm_product_i18n_form_capture($form){
    $this->init_page();
    $this->sql->set('product_i18n/params/product_id/value', wfRequest::get('product_id'));
    $this->sql->set('product_i18n/params/language/value', wfRequest::get('language'));
    $rs = $this->executeSQL($this->sql->get('product_i18n'));
    if(!$rs->get('0')){
      $this->sql->set('product_i18n_insert/params/product_id/value', wfRequest::get('product_id'));
      $this->sql->set('product_i18n_insert/params/language/value', wfRequest::get('language'));
      $this->executeSQL($this->sql->get('product_i18n_insert'));
    }
    $this->sql->set('product_i18n_update/params/product_id/value', wfRequest::get('product_id'));
    $this->sql->set('product_i18n_update/params/language/value', wfRequest::get('language'));
    $this->sql->set('product_i18n_update/params/name/value', wfRequest::get('name'));
    $this->sql->set('product_i18n_update/params/description/value', wfRequest::get('description'));
    $this->sql->set('product_i18n_update/params/description_more/value', wfRequest::get('description_more'));
    $this->executeSQL($this->sql->get('product_i18n_update'));
    return array("PluginWfAjax.update('product_i18n_".wfRequest::get('language')."');$('#modal_product_i18n_form').modal('hide');");
  }
  /**
   * 
   */
  public function frm_type_i18n_form_capture($form){
    $this->init_page();
    $this->sql->set('type_i18n/params/product_type_id/value', wfRequest::get('product_type_id'));
    $this->sql->set('type_i18n/params/language/value', wfRequest::get('language'));
    $rs = $this->executeSQL($this->sql->get('type_i18n'));
    if(!$rs->get('0')){
      $this->sql->set('type_i18n_insert/params/product_type_id/value', wfRequest::get('product_type_id'));
      $this->sql->set('type_i18n_insert/params/language/value', wfRequest::get('language'));
      $this->executeSQL($this->sql->get('type_i18n_insert'));
    }
    $this->sql->set('type_i18n_update/params/product_type_id/value', wfRequest::get('product_type_id'));
    $this->sql->set('type_i18n_update/params/language/value', wfRequest::get('language'));
    $this->sql->set('type_i18n_update/params/name/value', wfRequest::get('name'));
    $this->sql->set('type_i18n_update/params/description/value', wfRequest::get('description'));
    $this->sql->set('type_i18n_update/params/description_more/value', wfRequest::get('description_more'));
    $this->executeSQL($this->sql->get('type_i18n_update'));
    return array("PluginWfAjax.update('type_i18n_".wfRequest::get('language')."');$('#modal_type_i18n_form').modal('hide');");
  }
  /**
   * Validate if product exist.
   */
  public function validate_product_id($field, $form, $data = array()){
    if(wfArray::get($form, "items/$field/is_valid")){
      /**
       * Check if product exist.
       * Same check for existing or new post.
       */
      if(wfArray::get($form, "items/$field/post_value") != wfArray::get($form, "items/ws_p_id/post_value")){
        $this->init_page();
        $this->sql->set('product_exist/params/id/value', wfArray::get($form, "items/$field/post_value"));
        $rs_exist = $this->executeSQL($this->sql->get('product_exist'));
        if($rs_exist->get('0/id')){
          $form = wfArray::set($form, "items/$field/is_valid", false);
          $form = wfArray::set($form, "items/$field/errors/", __('?label already exist for other product!', array('?label' => wfArray::get($form, "items/$field/label"))));
        }
      }
    }
    return $form;
  }
  public function page_product_form(){
    $widget = wfDocument::createWidget('wf/form_v2', 'render', 'yml:/plugin/wshop/editor_v1/form/product_form.yml');
    wfDocument::renderElement(array($widget));
  }
  public function page_product_capture(){
    $widget = wfDocument::createWidget('wf/form_v2', 'capture', 'yml:/plugin/wshop/editor_v1/form/product_form.yml');
    wfDocument::renderElement(array($widget));
  }
  /**
   * Validate if product exist.
   */
  public function validate_type_id($field, $form, $data = array()){
    if(wfArray::get($form, "items/$field/is_valid")){
      /**
       * Check if product exist.
       * Same check for existing or new post.
       */
      if(wfArray::get($form, "items/$field/post_value") != wfArray::get($form, "items/ws_t_id/post_value")){
        $this->init_page();
        $this->sql->set('type_exist/params/id/value', wfArray::get($form, "items/$field/post_value"));
        $rs_exist = $this->executeSQL($this->sql->get('type_exist'));
        if($rs_exist->get('0/id')){
          $form = wfArray::set($form, "items/$field/is_valid", false);
          $form = wfArray::set($form, "items/$field/errors/", __('?label already exist for other type!', array('?label' => wfArray::get($form, "items/$field/label"))));
        }
      }
    }
    return $form;
  }
  public function page_type_form(){
    $widget = wfDocument::createWidget('wf/form_v2', 'render', 'yml:/plugin/wshop/editor_v1/form/type_form.yml');
    wfDocument::renderElement(array($widget));
  }
  public function page_type_capture(){
    $widget = wfDocument::createWidget('wf/form_v2', 'capture', 'yml:/plugin/wshop/editor_v1/form/type_form.yml');
    wfDocument::renderElement(array($widget));
  }
  
  public function page_types(){
    $this->init_page();
    $page = $this->getYml('page/types.yml');
    $table = $this->getYml('html_object/table.yml');
    $table->setById('thead_tr', 'innerHTML', array(
      wfDocument::createHtmlElement('th', 'ID'),
      wfDocument::createHtmlElement('th', 'Sort'),
      wfDocument::createHtmlElement('th', 'Name')
    ));
    /**
     * Get from db.
     */
    $this->sql->set('types/params/language/value', wfI18n::getLanguage());
    $rs = $this->executeSQL($this->sql->get('types'));
    $tr = array();
    foreach ($rs->get() as $key => $value) {
      $item = new PluginWfArray($value);
      $tr[] = wfDocument::createHtmlElement('tr', array(
        wfDocument::createHtmlElement('td', $item->get('id')),
        wfDocument::createHtmlElement('td', $item->get('sort')),
        wfDocument::createHtmlElement('td', $item->get('name'))
      ), array('onclick' => "PluginWfBootstrapjs.modal({id: 'modal_type', url: 'type/ws_t_id/".$item->get('id')."', lable: 'Type', size: 'lg'});"));
    }
    $table->setById('tbody', 'innerHTML', $tr);
    $page->setById('content', 'innerHTML', array($table->get()));
    /**
     * 
     */
    wfDocument::mergeLayout($page->get());
 }
  /**
   * Type view.
   */
  public function page_type(){
    $this->init_page();
    $page = $this->getYml('page/type.yml');
    $id = wfArray::get($GLOBALS, 'sys/settings/plugin/wshop/editor_v1/type/id');
    /**
     * Images.
     */
    $div = wfDocument::createHtmlElement('div', null, array('id' => "type_images", 'style' => 'min-height:200px'));
    $page->setById('language', 'innerHTML/', $div);
    $div = wfDocument::createHtmlElement('script', "PluginWfAjax.load('type_images', 'type_images/ws_t_id/$id');");
    $page->setById('language', 'innerHTML/', $div);
    /**
     * Add i18n.
     */
    foreach (wfI18n::getLanguages() as $key => $language) {
      $div = wfDocument::createHtmlElement('div', null, array('id' => "type_i18n_$language", 'style' => 'min-height:200px'));
      $page->setById('language', 'innerHTML/', $div);
      $div = wfDocument::createHtmlElement('script', "PluginWfAjax.load('type_i18n_$language', 'type_i18n/language/$language/ws_t_id/$id');");
      $page->setById('language', 'innerHTML/', $div);
    }
    /**
     * Render.
     */
    wfDocument::mergeLayout($page->get());
  }
  /**
   * 
   */
  public function page_product_delete_confirm(){
    $this->init_page();
    $page = $this->getYml('page/product_delete_confirm.yml');
    wfDocument::mergeLayout($page->get());
  }
  /**
   * 
   */
  public function page_type_delete_confirm(){
    $this->init_page();
    $page = $this->getYml('page/type_delete_confirm.yml');
    wfDocument::mergeLayout($page->get());
  }
  /**
   * 
   */
  public function page_product_delete(){
    $this->init_page();
    $id = wfArray::get($GLOBALS, 'sys/settings/plugin/wshop/editor_v1/product/id');
    /**
     * Delete images.
     */
    $this->delete_product_images($id);
    /**
     * Delete from db.
     */
    $this->sql->set('product_delete/params/id/value', $id);
    $this->executeSQL($this->sql->get('product_delete'));
    /**
     * 
     */
    exit(json_encode(array('success' => true, 'script' => array("PluginWfAjax.update('start_content');$('.modal').modal('hide');"))));
  }
  private function delete_product_images($id){
    $dir = wfSettings::replaceDir('[web_dir]/data/theme/[theme]/wshop/product');
    $exist = wfFilesystem::fileExist($dir.'/'.$id.'.jpg');
    if($exist){
      wfFilesystem::delete($dir.'/'.$id.'.jpg');
    }
    for($i=1;$i<5;$i++){
      $exist = wfFilesystem::fileExist($dir.'/'.$id.'_'.$i.'.jpg');
      if($exist){
        wfFilesystem::delete($dir.'/'.$id.'_'.$i.'.jpg');
      }
    }
    return null;
  }
  public function page_type_delete(){
    $this->init_page();
    $id = wfArray::get($GLOBALS, 'sys/settings/plugin/wshop/editor_v1/type/id');
    /**
     * Check if products exist.
     */
    $this->sql->set('products_by_type/params/product_type_id/value', $id);
    $rs = $this->executeSQL($this->sql->get('products_by_type'));
    if($rs->get('0')){
      exit(json_encode(array('success' => false, 'script' => array("alert('Can not delete type who has products.');"))));
    }
    /**
     * Delete images.
     */
    $this->delete_type_images($id);
    /**
     * Delete from db.
     */
    $this->sql->set('type_delete/params/id/value', $id);
    $this->executeSQL($this->sql->get('type_delete'));
    /**
     * 
     */
    exit(json_encode(array('success' => true, 'script' => array("PluginWfAjax.update('start_content');$('.modal').modal('hide');"))));
  }
  /**
   * 
   */
  private function delete_type_images($id){
    $dir = wfSettings::replaceDir('[web_dir]/data/theme/[theme]/wshop/type');
    $exist = wfFilesystem::fileExist($dir.'/'.$id.'.jpg');
    if($exist){
      wfFilesystem::delete($dir.'/'.$id.'.jpg');
    }
    return null;
  }
  /**
   * 
   */
  private function getYml($file){
    return new PluginWfYml(wfArray::get($GLOBALS, 'sys/app_dir').'/plugin/wshop/editor_v1/'.$file);
  }
  /**
   * 
   */
  private function executeSQL($sql){
    wfPlugin::includeonce('wf/mysql');
    $mysql = new PluginWfMysql();
    $mysql->open($this->settings->get('mysql'));
    $mysql->execute($sql);
    $record = new PluginWfArray($mysql->getStmtAsArray());
    return $record;
  }
}
