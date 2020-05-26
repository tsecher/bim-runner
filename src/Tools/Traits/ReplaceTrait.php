<?php

namespace BimRunner\Tools\Traits;

trait ReplaceTrait {
    use OSTrait;

    protected $str_content_id = '_@_';

    /**
     * Copy et replace.
     *
     * @param $from
     * @param $to
     * @param $replace
     * @param array $idWrappers
     */
    public function copyAndReplace($from, $to, $replace, $idWrappers = []) {
        if (file_exists($from)) {
            $data = file_get_contents($from);
            $data = $this->replace($replace, $data, $idWrappers);
            file_put_contents($to, $data);
        }
    }

    /**
     * Replace les data dans sujets.
     *
     * @param $data
     * @param $subject
     * @param array $idWrappers
     *
     * @return string
     */
    public function replace($data, $subject, $idWrappers = []) {
        $dataToReplace = $data;

        if (!empty($idWrappers)) {
            foreach ($idWrappers as $idWrapper) {
                foreach ($data as $key => $value) {
                    $dataToReplace[str_replace($this->str_content_id, $key, $idWrapper)] = $value;
//          unlink($dataToReplace[$key]);
                }
            }
            $dataToReplace = array_reverse($dataToReplace);
        }

        return str_replace(array_keys($dataToReplace), array_values($dataToReplace), $subject);
    }

    /**
     * Append $source file content into $into.
     *
     * @param $source
     * @param $into
     * @params $afterSeparator
     *
     * @throws \Exception
     */
    public function append($source, $into, $afterSeparator, $include = TRUE) {
        // Get sources.
        $data = ['source' => ['file' => $source], 'into' => ['file' => $into]];
        foreach ($data as &$fileData) {
            if (file_exists($fileData['file'])) {
                $fileData['content'] = file_get_contents($fileData['file']);
            }
            else {
                throw new \Exception($fileData['file'] . ' does not exist');
            }
        }

        // Init content.
        if (!empty($afterSeparator)) {
            $intoContent = explode($afterSeparator, $data['into']['content']);
            array_pop($intoContent);
            $intoContent = implode($intoContent);
            if( $include){
              $intoContent .= $afterSeparator . PHP_EOL;
            }
        }
        else {
            $intoContent = $data['into']['content'];
        }

        // Append.
        file_put_contents($into, $intoContent . $data['source']['content']);
    }

    /**
     * Rename $from en $to.
     *
     * @param $from
     * @param $to
     * @param string $dirname
     * @param bool $delete
     */
    public function rename($from, $to, $dirname = '', $delete = FALSE) {

        if ($delete) {
            rename($dirname . '/' . $from, $dirname . '/' . $to);
        }
        else {
            $this->command('cp ' . $from . ' ' . $to, $dirname);
        }
    }

}
