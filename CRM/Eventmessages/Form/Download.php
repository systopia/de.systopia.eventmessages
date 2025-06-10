<?php
/*-------------------------------------------------------+
| SYSTOPIA Event Messages                                |
| Copyright (C) 2021 SYSTOPIA                            |
| Author: J. Schuppe (schuppe@systopia.de)               |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+--------------------------------------------------------*/

use CRM_Eventmessages_ExtensionUtil as E;

/**
 * Page to download PDFs and go back to the search result.
 */
class CRM_Eventmessages_Form_Download extends CRM_Core_Form {
  /**
   * @var string
   *   The tmp folder holding the PDFs.
   */
  public $tmp_folder;

  /**
   * @var string
   *   The URL to return to.
   */
  public $return_url;

  public function buildQuickForm() {
    $this->tmp_folder = CRM_Utils_Request::retrieve(
        'tmp_folder',
        'String',
        $this
    );
    $this->return_url = CRM_Utils_Request::retrieve(
        'return_url',
        'String',
        $this
    );

    $this->setTitle(E::ts('Your PDF letters are ready for download.'));
    $this->addButtons(
        [
            [
              'type' => 'submit',
              'name' => E::ts('Download'),
              'icon' => 'fa-download',
              'isDefault' => TRUE,
            ],
            [
              'type' => 'done',
              'name' => E::ts('Back to Search'),
              'isDefault' => FALSE,
            ],
        ]
    );
    parent::buildQuickForm();
  }

  public function postProcess() {
    $vars = $this->exportValues();
    // "Download" button clicked.
    if (isset($vars['_qf_Download_submit'])) {
      // Verify folder (naming convention).
      if (!preg_match('#/eventmessages_pdf_generator_\w+$#', $this->tmp_folder)) {
        throw new Exception('Illegal path.');
      }
      try {
        $filename = $this->tmp_folder . DIRECTORY_SEPARATOR . 'eventmessages_letters.zip';
        $data = file_get_contents($filename);
        CRM_Utils_System::download(basename($filename), 'application/zip', $data);
      }
      catch (Exception $ex) {
        CRM_Core_Session::setStatus(
        E::ts('Error downloading PDF files: %1', [1 => $ex->getMessage()]),
        E::ts('Download Error'),
        'error'
          );
      }
    }
    // "Back to search result" button clicked.
    elseif (isset($vars['_qf_Download_done'])) {
      // Delete temporary folder and iths content.
      foreach (scandir($this->tmp_folder) as $file) {
        if ($file != '.' && $file != '..') {
          unlink($this->tmp_folder . DIRECTORY_SEPARATOR . $file);
        }
      }
      rmdir($this->tmp_folder);

      // Redirect to search result.
      CRM_Utils_System::redirect(base64_decode($this->return_url));
    }
    parent::postProcess();
  }

}
