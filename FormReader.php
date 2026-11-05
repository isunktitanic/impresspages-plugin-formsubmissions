<?php

namespace Plugin\FormSubmissions;

/**
 * Builds the same form the Form widget showed the visitor, so the submitted values can be read
 * exactly the way the widget reads them for its email.
 */
class FormReader extends \Ip\Internal\Content\Widget\Form\Controller
{
    public function __construct()
    {
        parent::__construct('Form', 'Content', true);
    }

    public function buildForm($widgetId, $data)
    {
        return $this->createForm($widgetId, $data);
    }
}
