<?php

namespace WPFormsConvertKit\Vendor\GuzzleHttp;

use WPFormsConvertKit\Vendor\Psr\Http\Message\MessageInterface;
interface BodySummarizerInterface
{
    /**
     * Returns a summarized message body.
     */
    public function summarize(MessageInterface $message) : ?string;
}
