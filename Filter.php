<?php

namespace Plugin\FormSubmissions;

class Filter
{
    /**
     * The Form widget validates a submission, emails it and answers with JSON. When that answer says
     * "success", keep a copy. Nothing changes for the visitor if saving fails.
     */
    public static function ipSendResponse($response, $info = null)
    {
        if (!($response instanceof \Ip\Response\Json) || ipRequest()->getPost('sa') !== 'Content.widgetPost') {
            return $response;
        }
        $content = $response->getContent();
        if (!is_array($content) || ($content['status'] ?? null) !== 'success') {
            return $response;
        }

        try {
            Model::store((int)ipRequest()->getPost('widgetId'), ipRequest()->getPost());
        } catch (\Throwable $e) {
            ipLog()->error('FormSubmissions.saveFailed: {message}', array('message' => $e->getMessage()));
        }
        return $response;
    }
}
