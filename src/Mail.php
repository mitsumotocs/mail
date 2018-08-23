<?php
class Mail
{
    const HEADER_SEPARATOR = "\r\n";
    const LANGUAGE = 'Japanese';
    const ENCODING = 'UTF-8';

    public $data = [];
    protected $template;
    protected $subject;
    protected $message;
    protected $headers = [];

    /**
     * @param string $text
     * @param array $data
     * @return string
     */
    protected static function interpolate($text, array $data = [])
    {
        $replace = [];
        foreach ($data as $key => $val) {
            if (!is_array($val) && (!is_object($val) || method_exists($val, '__toString'))) {
                $replace['{' . $key . '}'] = $val;
            }
        }
        return strtr($text, $replace);
    }

    /**
     * Mail constructor.
     * @param string|null $template
     */
    public function __construct($template = null)
    {
        if (isset($template)) {
            $this->setTemplate($template);
        }
    }

    /**
     * @param string $file
     * @return $this
     */
    public function setTemplate($file)
    {
        $this->template = $file;
        return $this;
    }

    /**
     * @param string $name
     * @param string $value
     * @return $this
     */
    public function addHeader($name, $value)
    {
        $this->headers[] = sprintf('%s: %s', $name, $value);
        return $this;
    }

    /**
     * @return void
     * @throws \RuntimeException|\LogicException
     */
    protected function parseTemplate()
    {
        if (isset($this->template)) {
            if (is_readable($this->template)) {
                $lines = file($this->template);
                $this->subject = trim(array_shift($lines));
                $this->message = trim(implode('', $lines));
            } else {
                throw new \RuntimeException(sprintf('Template file "%s" is not readable.', $this->template));
            }
        } else {
            throw new \LogicException('You need to give a template file.');
        }
    }

    /**
     * @return string
     */
    public function getHeader()
    {
        return implode(static::HEADER_SEPARATOR, $this->headers);
    }

    /**
     * @return string
     */
    public function getSubject()
    {
        return static::interpolate($this->subject, $this->data);
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return static::interpolate($this->message, $this->data);
    }

    /**
     * @return string
     */
    public function preview()
    {
        $this->parseTemplate();
        return implode(static::HEADER_SEPARATOR, [
            $this->getHeader(),
            sprintf('Subject: %s', $this->getSubject()),
            static::HEADER_SEPARATOR,
            $this->getMessage()
        ]);
    }

    /**
     * @param string $to
     * @param string $from
     * @return bool
     */
    public function send($to, $from)
    {
        $this->parseTemplate();
        $this->addHeader('From', $from);
        $this->addHeader('Reply-To', $from);

        mb_language(static::LANGUAGE);
        mb_internal_encoding(static::ENCODING);
        return mb_send_mail($to, $this->getSubject(), $this->getMessage(), $this->getHeader());
    }
}