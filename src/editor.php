<?php

namespace SimpleFileManager;

/**
 * @author Uğur Gülay <ugur.gulay@tsoft.com.tr>
 * Simpe File Editor
 */
class Editor
{

    /**
     * Allowed Extensions
     */
    private $allowed = [
        'php', 'sh', 'bs', 'bash', 'c',
        'html', 'shtml', 'css', 'js', 'xml',
        'conf', 'config', 'ini', 'txt', 'licence', 'license',
        'csv', 'tsv', 'doc', 'docx', 'zip',
        'rar', 'gz', 'sql', 'xls', 'xlsx', 'json',
    ];

    public $rootPath = '';

    public function __construct()
    {
        $cfg = parse_ini_file(dirname(__FILE__) . '/config.ini');
        $this->rootPath = $cfg['WORKING_DIR'];
    }

    public function setWorkingDir($dir = '')
    {
        $this->rootPath = $dir;
    }

    public function checkRoot($path)
    {
        return mb_strpos($path, $this->rootPath) === false ? false : true;
    }

    public function getDir($path = null)
    {

        $path = !empty($path) ? $path : $this->rootPath;

        $files = scandir($path);

        asort($files);

        $res = [];

        foreach ($files as $file) {

            $fullPath = $path . '/' . $file;

            if (in_array($file, ['.', '..'])) {
                continue;
            }

            /*
            if (SHOW_PHP_SELF === false) {
            if (__FILE__ == $fullPath) {
            continue;
            }
            }
             */

            if (!is_dir($fullPath) && !$this->checkExtension($fullPath)) {
                continue;
            }

            $res[] = [
                'dir' => $path,
                'name' => $file,
                'fullPath' => $fullPath,
                'ext' => pathinfo($fullPath, PATHINFO_EXTENSION),
                'isDir' => is_dir($fullPath),
                'readable' => is_readable($fullPath),
                'writeable' => is_writable($fullPath),
            ];
        }

        return $res;
    }

    /**
     * Get File Content
     */
    public function getFile($file = null)
    {

        if (!$this->checkRoot($file)) {
            return ['status' => false, 'msg' => 'Ana Dizin Üzerine Çıkamazsınız : ' . $file];
        }

        if (!is_readable($file)) {
            return ['status' => false, 'msg' => 'Dosya Okunamadı : ' . $file];
        }

        if (!$this->checkExtension($file)) {
            return ['status' => false, 'msg' => 'Bu Uzantıya İzin Verilmemektedir : ' . $file];
        }

        $res = file_get_contents($file);

        if ($res === false) {
            return ['status' => false, 'content' => $res, 'msg' => 'Dosya Okunamadı : ' . $file];
        }

        return ['status' => true, 'content' => $res, 'msg' => 'Dosya Açıldı : ' . $file];
    }

    /**
     * Create a directory with 0755 permission
     */
    public function createDir($dir = null)
    {

        if (!$this->checkRoot($dir)) {
            return ['status' => false, 'msg' => 'Ana Dizin Üzerine Çıkamazsınız : ' . $dir];
        }

        if (is_dir($dir)) {
            return ['status' => false, 'msg' => 'Klasör Zaten Mevcut : ' . $dir];
        }

        $res = mkdir($dir, 0755, true);

        if ($res === true) {
            return ['status' => true, 'msg' => 'Klasör Oluşturuldu : ' . $dir];
        }

        if ($res === false) {
            return ['status' => false, 'msg' => 'Klasör Oluşturulamadı : ' . $dir];
        }

    }

    /**
     * Create a file with 0664 permission
     */
    public function createFile($file = null, $content = '')
    {

        if (!$this->checkRoot($file)) {
            return ['status' => false, 'msg' => 'Ana Dizin Üzerine Çıkamazsınız : ' . $file];
        }

        if (file_exists($file)) {
            return ['status' => false, 'msg' => 'Dosya Zaten Mevcut : ' . $file];
        }

        if (!$this->checkExtension($file)) {
            return ['status' => false, 'msg' => 'Bu Uzantıya İzin Verilmemektedir : ' . $file];
        }

        $res = file_put_contents($file, (string) $content);
        chmod($file, 0664);

        if ($res === false) {
            return ['status' => false, 'msg' => 'Dosya Oluşturulamadı : ' . $file];
        }

        return ['status' => true, 'msg' => 'Dosya Oluşturuldu : ' . $file];
    }

    /**
     * Saves a current file
     */
    public function saveFile($file = null, $content = '')
    {

        if (!$this->checkRoot($file)) {
            return ['status' => false, 'msg' => 'Ana Dizin Üzerine Çıkamazsınız : ' . $file];
        }

        if (!is_readable($file)) {
            return ['status' => false, 'msg' => 'Dosya Okunamadı : ' . $file];
        }

        if (!$this->checkExtension($file)) {
            return ['status' => false, 'msg' => 'Bu Uzantıya İzin Verilmemektedir : ' . $file];
        }

        $res = file_put_contents($file, (string) $content);

        if ($res === false) {
            return ['status' => false, 'msg' => 'Dosya Kaydedilemedi : ' . $file];
        }

        return ['status' => true, 'msg' => 'Dosya Kaydedildi : ' . $file];
    }

    /**
     * File Ext. controller
     */
    public function checkExtension($filepath = false)
    {
        $control = preg_match('/([^\.]+$)/i', $filepath, $extension);
        if ($extension) {
            $extension = $extension[1];
            if (in_array($extension, $this->allowed)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Deletes the give dir. path
     */
    public function deleteDirAction($path)
    {

        if (!$this->checkRoot($path)) {
            return ['status' => false, 'msg' => 'Ana Dizin Üzerine Çıkamazsınız : ' . $path];
        }

        if (is_dir($path)) {

            $files = glob($path . '*', GLOB_MARK); //GLOB_MARK adds a slash to directories returned

            foreach ($files as $file) {
                $this->deleteDirAction($file);
            }

            rmdir($path);
        } elseif (is_file($path)) {
            unlink($path);
        }
    }

    /**
     * Deletes a given file path.
     */
    public function deleteFileAction($path)
    {

        if (!$this->checkRoot($path)) {
            return ['status' => false, 'msg' => 'Ana Dizin Üzerine Çıkamazsınız : ' . $path];
        }

        if (file_exists($path) && !is_dir($path) && is_file($path)) {
            return unlink($path);
        }
        return false;
    }

    public function render()
    {
        ob_start();
        require dirname(__FILE__) . '/view.php';
        return ob_get_clean();
    }

}
