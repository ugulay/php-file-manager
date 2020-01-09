
# php-file-manager

For changing working dir on editor u need the edit config.ini file in src

    require __DIR__ . '/vendor/autoload.php';
    use SimpleFileManager\Editor;
    $e = new Editor;
    echo $e->render();


