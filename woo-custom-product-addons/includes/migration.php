<?php

namespace Acowebs\WCPA\Free;

class Migration
{

    private $format = '';

    public function __construct()
    {
        $this->format = get_option('date_format');
    }

    public function fieldMigrationsToV5(&$json_decode, $form_id)
    {
        foreach ($json_decode as $sectionKey => $section) {
            foreach ($section->fields as $rowIndex => $row) {
                foreach ($row as $colIndex => $field) {
                    /**
                     * convert text field with sub type email to email field
                     */
                    if ($field->type == 'text' && $field->subtype == "email") {
                        $field->type = 'email';
                    }
                    if ($field->type == 'text' && $field->subtype == "url") {
                        $field->type = 'url';
                    }
                    if ($field->type == 'paragraph') {
                        $field->type = 'content';
                        $field->value = $field->label;
                        $field->label = '';
                        $field->name = $field->elementId;
                        $field->contentType = 'plain';
                    }
                    if ($field->type == 'date' || $field->type == 'datetime-local') {
                        $field->picker_mode = 'single';
                    }
                    if (isset($field->label)) {
                        $field->label = html_entity_decode($field->label);
                    }
                    if (isset($field->values)) {
                        foreach ($field->values as $v) {
                            if (isset($v->label)) {
                                $v->label = html_entity_decode($v->label);
                            }
                        }
                    }
                }
            }
        }
    }


}
