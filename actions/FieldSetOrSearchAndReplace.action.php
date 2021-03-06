<?php
// this action is not ready for use yet, which is why the filename doesn't have ".action" in it, so it won't be installed

class FieldSetOrSearchAndReplace extends ProcessAdminActions {

    protected $description = 'Set field values, or search and replace text in field values from a filtered selection of pages and fields.';
    protected $notes = 'This can be very destructive!';
    protected $author = 'Adrian Jones';
    protected $authorLinks = array(
        'pwforum' => '985-adrian',
        'pwdirectory' => 'adrian-jones',
        'github' => 'adrianbj',
    );

    protected function defineOptions() {

        $fieldOptions = array();
        foreach($this->fields as $field) {
            if (!$field->type instanceof FieldtypeText) continue;
            if(count($field->getFieldgroups()) !== 0) $fieldOptions[$field->id] = $field->label ? $field->name . ' (' . $field->label . ')' : $field->name;
        }

        return array(
            array(
                'name' => 'selector',
                'label' => 'Selector',
                'description' => 'Define selector to match the pages you want to edit.',
                'notes' => 'If none defined, it will use all pages except admin and trash.',
                'type' => 'selector'
            ),
            array(
                'name' => 'fields',
                'label' => 'Fields',
                'description' => 'Choose the field(s) whose values you want to set, or search and replace.',
                'notes' => 'If none defined, it will use all text based fields.',
                'type' => 'AsmSelect',
                'options' => $fieldOptions
            ),
            array(
                'name' => 'search',
                'label' => 'Search',
                'description' => 'Enter text to search for',
                'notes' => 'You can use plain text (str_replace), or a regex (starting and ending with "/") (preg_replace). Whatever is matched here will be replaced by the content of the "Set or Replace" field.',
                'type' => 'text',
                'columnWidth' => 50
            ),
            array(
                'name' => 'setOrReplace',
                'label' => 'Set Or Replace',
                'description' => 'Enter text to set or replace',
                'notes' => 'If no "search" value is defined, this will simply set the value, completely overwriting any existing content.',
                'type' => 'text',
                'columnWidth' => 50,
                'required' => true
            )
        );
    }


    protected function executeAction($options) {

        $count = 0;
        $pageSelector = $options['selector'] ?: "has_parent!=".$this->config->adminRootPageID.",id!=".$this->config->adminRootPageID."|".$this->config->trashPageID.",status<".Page::statusTrash.",include=all";
        foreach($this->pages->find($pageSelector) as $p) {
            $p->of(false);

            if($options['fields']) {
                $fieldOptions = $options['fields'];
            }
            else {
                $fieldOptions = array();
                foreach($this->fields as $field) {
                    if (!$field->type instanceof FieldtypeText) continue;
                    if($p->template->hasField($field)) $fieldOptions[] = $field->id;
                }
            }

            foreach($fieldOptions as $field) {
                $fieldName = $this->fields->get((int)$field)->name;
                if(!$p->template->hasField($fieldName)) continue;
                if($options['search'] != '') {
                    if($options['search'][0] === '/' && $options['search'][strlen($options['search'])-1] === '/') {
                        $p->$fieldName = preg_replace($options['search'], $options['setOrReplace'], $p->$fieldName);
                    }
                    else {
                        $p->$fieldName = str_replace($options['search'], $options['setOrReplace'], $p->$fieldName);
                    }
                }
                else {
                    $p->$fieldName = $options['setOrReplace'];
                }
                $p->save($fieldName);
            }
            $count++;
        }

        $this->successMessage = 'Successfully applied to ' . $count .' page' . _n('', 's', $count) . '.';
        return true;

    }

}