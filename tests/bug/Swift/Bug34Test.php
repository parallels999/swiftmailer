<?php

class Swift_Bug34Test extends PHPUnit\Framework\TestCase
{
    protected function setUp()
    {
        Swift_Preferences::getInstance()->setCharset('utf-8');
    }

    public function testEmbeddedFilesWithMultipartDataCreateMultipartRelatedContentAsAnAlternative()
    {
        $message = new Swift_Message();
        $message->setCharset('utf-8');
        $message->setSubject('test subject');
        $message->addPart('plain part', 'text/plain');

        $image = new Swift_Image('<image data>', 'image.gif', 'image/gif');
        $cid = $message->embed($image);

        $message->setBody('<img src="'.$cid.'" />', 'text/html');

        $message->setTo(['user@domain.tld' => 'User']);

        $message->setFrom(['other@domain.tld' => 'Other']);
        $message->setSender(['other@domain.tld' => 'Other']);

        $id = $message->getId();
        $date = preg_quote($message->getDate()->format('r'), '~');
        $boundary = $message->getBoundary();
        $cidVal = $image->getId();

        $this->assertMatchesRegularExpression(
            '~^'.
            'Sender: Other <other@domain.tld>'."\r\n".
            'Message-ID: <'.$id.'>'."\r\n".
            'Date: '.$date."\r\n".
            'Subject: test subject'."\r\n".
            'From: Other <other@domain.tld>'."\r\n".
            'To: User <user@domain.tld>'."\r\n".
            'MIME-Version: 1.0'."\r\n".
            'Content-Type: multipart/alternative;'."\r\n".
            ' boundary="'.$boundary.'"'."\r\n".
            "\r\n\r\n".
            '--'.$boundary."\r\n".
            'Content-Type: text/plain; charset=utf-8'."\r\n".
            'Content-Transfer-Encoding: quoted-printable'."\r\n".
            "\r\n".
            'plain part'.
            "\r\n\r\n".
            '--'.$boundary."\r\n".
            'Content-Type: multipart/related;'."\r\n".
            ' boundary="(.*?)"'."\r\n".
            "\r\n\r\n".
            '--\\1'."\r\n".
            'Content-Type: text/html; charset=utf-8'."\r\n".
            'Content-Transfer-Encoding: quoted-printable'."\r\n".
            "\r\n".
            '<img.*?/>'.
            "\r\n\r\n".
            '--\\1'."\r\n".
            'Content-Type: image/gif; name=image.gif'."\r\n".
            'Content-Transfer-Encoding: base64'."\r\n".
            'Content-ID: <'.$cidVal.'>'."\r\n".
            'Content-Disposition: inline; filename=image.gif'."\r\n".
            "\r\n".
            preg_quote(base64_encode('<image data>'), '~').
            "\r\n\r\n".
            '--\\1--'."\r\n".
            "\r\n\r\n".
            '--'.$boundary.'--'."\r\n".
            '$~D',
            $message->toString()
        );
    }
}
