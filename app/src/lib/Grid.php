<?php

namespace Leochenftw;

use \SilverStripe\Forms\GridField\GridField;
use \SilverStripe\Forms\GridField\GridFieldConfig_Base;
use \SilverStripe\Forms\GridField\GridFieldConfig_RecordViewer;
use \SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use \SilverStripe\Forms\GridField\GridFieldConfig_RelationEditor;
use UndefinedOffset\SortableGridField\Forms\GridFieldSortableRows;

class Grid
{
    public static function make($name, $label = '', $source = null, $sortable = true, $gridHeaderType = 'GridFieldConfig_RecordEditor')
    {
        /*
        GridFieldConfig_Base
        GridFieldConfig_RecordViewer
        GridFieldConfig_RecordEditor
        GridFieldConfig_RelationEditor
        */
        if ($label == '') {
            $label = $name;
        }

        $grid       =   GridField::create($name, $label, $source);

        if ($gridHeaderType == 'GridFieldConfig_Base') {
            $config =   GridFieldConfig_Base::create();
        }

        if ($gridHeaderType == 'GridFieldConfig_RecordViewer') {
            $config =   GridFieldConfig_RecordViewer::create();
        }

        if ($gridHeaderType == 'GridFieldConfig_RecordEditor') {
            $config =   GridFieldConfig_RecordEditor::create();
        }

        if ($gridHeaderType == 'GridFieldConfig_RelationEditor') {
            $config =   GridFieldConfig_RelationEditor::create();
        }

        if ($sortable) {
            $config->addComponent($sortable = new GridFieldSortableRows('Sort'));
            $sortable->setUpdateVersionedStage('Live');
        }

        $grid->setConfig($config);
        return $grid;
    }
}
