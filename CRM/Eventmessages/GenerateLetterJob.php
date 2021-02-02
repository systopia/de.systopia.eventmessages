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
 * Queue item for generating letters for participants
 */
class CRM_Eventmessages_GenerateLetterJob
{
    /**
     * @var string $op
     *  The operation to perform during the job.
     */
    protected $op = 'run';

    /**
     * @var string $title
     *   The job title.
     */
    public $title = '';

    /**
     * @var int[] $participant_ids
     *   List of participant IDs.
     */
    protected $participant_ids;

    /**
     * @var int $template_id
     *   The template to use for generating the letter.
     */
    protected $template_id;

    /**
     * @var string $temp_folder
     *   The temporary directory where PDFs are being stored for the current
     *   queue.
     */
    protected $temp_folder;

    public function __construct($op = 'run', $participant_ids, $template_id, $temp_folder, $title)
    {
        $this->op = $op;
        $this->participant_ids = $participant_ids;
        $this->template_id = $template_id;
        $this->temp_folder = $temp_folder;
        $this->title = $title;
    }

    public function run(): bool
    {
        switch ($this->op) {
            case 'init':
                if (file_exists($this->temp_folder)) {
                    unlink($this->temp_folder);
                }
                mkdir($this->temp_folder);
                break;
            case 'run':
                if (!empty($this->participant_ids)) {
                    // Load participants.
                    $participants = civicrm_api3(
                        'Participant',
                        'get',
                        [
                            'id' => ['IN' => $this->participant_ids],
                            'return' => 'id,contact_id,event_id,status_id',
                            'option.limit' => 0,
                        ]
                    );
                    // Generate PDF letters for participants.
                    foreach ($participants['values'] as $participant) {
                        try {
                            // Generate PDF letters.
                            $pdf = CRM_Eventmessages_GenerateLetter::generateLetterFor(
                                [
                                    'participant_id' => $participant['id'],
                                    'event_id' => $participant['event_id'],
                                    'from' => $participant['status_id'],
                                    'to' => $participant['status_id'],
                                    'rule' => 0,
                                    'template_id' => $this->template_id,
                                ]
                            );
                            $filename = $this->temp_folder . DIRECTORY_SEPARATOR . 'eventmessages_letter_' . $participant['id'] . '.pdf';
                            file_put_contents($filename, $pdf);
                        } catch (Exception $exception) {
                            Civi::log()->notice("EventMessages.GenerateLetterJob: Error generating letter for participant [{$participant['id']}]: " . $exception->getMessage());
                        }
                    }
                }
                break;
            case 'finish':
                // Zip PDF files.
                $archiveFileName = $this->temp_folder . DIRECTORY_SEPARATOR . 'eventmessages_letters.zip';
                $zip = new ZipArchive();
                // Get all PDF files in the temporary directory.
                $pdfs = preg_grep('~\.(pdf)$~i', scandir($this->temp_folder));
                if ($zip->open($archiveFileName, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE) === true) {
                    foreach ($pdfs as $id => $pdf_filename) {
                        $pdf_filename = $this->temp_folder . DIRECTORY_SEPARATOR . $pdf_filename;
                        $toRemove[] = $pdf_filename;
                        $opResult = $zip->addFile($pdf_filename, basename($pdf_filename));
                    }
                }
                $zip->close();

                // Remove temporary files.
                foreach ($toRemove as $file_to_remove) {
                    unlink($file_to_remove);
                }
                break;
        }
        return true;
    }
}
