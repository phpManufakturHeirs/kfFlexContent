/**
 * @license Copyright (c) 2003-2013, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.html or http://ckeditor.com/license
 */

// define the entries for the 'Styles' dropdown
CKEDITOR.stylesSet.add('flexcontent', [
    // Block-level styles.
    { name: '.clearfix', element: 'div', attributes: {'class':'clearfix'} },
	  
    // Inline styles.
    { name: 'Sample', element: 'samp' },
    { name: 'Variable', element: 'var' },
    { name: 'Code', element: 'code' },
    { name: 'Command, KBD', element: 'key' },
    { name: 'empty &lt;span>', element: 'span' },
    { name: 'Deleted Text', element: 'del' },
    
]);

CKEDITOR.editorConfig = function( config ) {
    // Define changes to default configuration here.
    // For the complete reference:
    // http://docs.ckeditor.com/#!/api/CKEDITOR.config

    // load the above styleSet 'flexcontent'
    config.stylesSet = 'flexcontent';
    
  // The toolbar groups arrangement, optimized for two toolbar rows.
    config.toolbar = [
          { name: 'clipboard', groups: [ 'clipboard', 'undo' ], items: [ 'Cut', 'Copy', 'Paste', 'PasteText', 'PasteFromWord', '-', 'Undo', 'Redo' ] },
          { name: 'editing', groups: [ 'find', 'selection' ] },
          { name: 'links', items: [ 'Link', 'hashtaglink', 'flexcontentlink', 'cmspagelink', 'Unlink', 'Anchor'] },
          { name: 'insert', items: [ 'Image', 'Table', 'SpecialChar' ] },
           { name: 'document', groups: [ 'mode', 'document', 'doctools' ], items: [ 'Source' ] },
          { name: 'tools', items: [ 'Maximize' ] },
          { name: 'others', items: [ '-' ] },
          '/',
          { name: 'basicstyles', groups: [ 'basicstyles', 'cleanup' ], items: [ 'Bold', 'Italic', 'Underline', '-', 'RemoveFormat' ] },
          { name: 'paragraph', groups: [ 'list', 'indent', 'blocks', 'align' ], items: [ 'NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', '-', 'Blockquote', '-', 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock' ] },
          { name: 'styles', items: [ 'Styles', 'Format' ] }
      ];


    // Remove some buttons, provided by the standard plugins, which we don't
    // need to have in the Standard(s) toolbar.
    config.removeButtons = 'Subscript,Superscript';

    // Se the most common block elements.
    config.format_tags = 'p;h1;h2;h3;pre';

    // utf8 need no entities!
    config.entities = false;
    config.basicEntities = false;
    config.entities_greek = false;
    config.entities_latin = false;
    
    // allow all contents, also <script> etc. !
    config.allowedContent = true;
};
