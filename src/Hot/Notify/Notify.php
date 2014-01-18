<?php
namespace Hot\Notify;

class Notify
{
    public function notify($title, $message)
    {
        $x_title = $this->esc($title);
        $x_message = $this->esc($message);

        if ($this->execute('which terminal-notifier')) {
            $this->execute("terminal-notifier -title '{$x_title}' -message '{$x_message}' -sender com.apple.Terminal");
        } else if ($this->execute('which notify-send')) {
            $this->execute("notify-send -t 2000 '{$x_title}' '$x_message'");
        } else {
            echo "\n";
            echo "{$title} - {$message}";
            echo "\n";
            echo "\n";
            echo "[NOTE]";
            echo "\n";
            echo "\n";
            echo file_get_contents(__DIR__ . '/Notify.doc.txt');
            echo "\n";
            echo "\n";
        }

    }

    protected function execute($command)
    {
        $status = null;
        $result = [];
        exec($command, $result, $status);
        return !$status ? $result : null;
    }

    /**
     * @param $text
     * @return string
     */
    protected function esc($text)
    {
        return str_replace("'", "\\''", $text);
    }


}