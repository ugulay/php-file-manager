<?php

namespace SimpleFileManager;

$editor = new \SimpleFileManager\Editor();

/**
 * Actions Controls
 */
$actionKey = 'action';

$action = false;

if (isset($_GET[$actionKey]) && !empty($_GET[$actionKey])) {
    $action = $_GET[$actionKey];
}

if (isset($_POST[$actionKey]) && !empty($_POST[$actionKey])) {
    $action = $_POST[$actionKey];
}

/**
 * Actions Starting
 */
if ($action !== false) {

    ob_clean();

    /**
     * Allowed functions
     */
    if (!in_array($action, ['dir', 'file', 'createDir', 'createFile', 'saveFile', 'deleteDir', 'deleteFile', 'uploadFiles', 'downloadFile'])) {
        die(json_encode(['status' => false, 'msg' => 'Geçersiz bir aksiyon çalıştırdınız.', 'action' => $action]));
    }

    /**
     * Directory and files
     */
    if ($action === 'dir') {

        $dirs = $editor->getDir($_POST["path"]);

        $assoc = [
            'folder' => 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiA/PjwhRE9DVFlQRSBzdmcgIFBVQkxJQyAnLS8vVzNDLy9EVEQgU1ZHIDEuMS8vRU4nICAnaHR0cDovL3d3dy53My5vcmcvR3JhcGhpY3MvU1ZHLzEuMS9EVEQvc3ZnMTEuZHRkJz48c3ZnIGVuYWJsZS1iYWNrZ3JvdW5kPSJuZXcgMCAwIDUwIDUwIiBoZWlnaHQ9IjUwcHgiIGlkPSJMYXllcl8xIiB2ZXJzaW9uPSIxLjEiIHZpZXdCb3g9IjAgMCA1MCA1MCIgd2lkdGg9IjUwcHgiIHhtbDpzcGFjZT0icHJlc2VydmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6eGxpbms9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkveGxpbmsiPjxyZWN0IGZpbGw9Im5vbmUiIGhlaWdodD0iNTAiIHdpZHRoPSI1MCIvPjxwYXRoIGQ9Ik00NiwxNXYtNCAgYzAtMS4xMDQtMC44OTYtMi0yLTJjMCwwLTI0LjY0OCwwLTI2LDBjLTEuNDY5LDAtMi40ODQtNC00LTRIM0MxLjg5Niw1LDEsNS44OTYsMSw3djR2Mjl2NGMwLDEuMTA0LDAuODk2LDIsMiwyaDM5ICBjMS4xMDQsMCwyLTAuODk2LDItMiIgZmlsbD0ibm9uZSIgc3Ryb2tlPSIjMDAwMDAwIiBzdHJva2UtbGluZWNhcD0icm91bmQiIHN0cm9rZS1taXRlcmxpbWl0PSIxMCIgc3Ryb2tlLXdpZHRoPSIyIi8+PHBhdGggZD0iTTEsNDRsNS0yNyAgYzAtMS4xMDQsMC44OTYtMiwyLTJoMzljMS4xMDQsMCwyLDAuODk2LDIsMmwtNSwyNyIgZmlsbD0ibm9uZSIgc3Ryb2tlPSIjMDAwMDAwIiBzdHJva2UtbGluZWNhcD0icm91bmQiIHN0cm9rZS1taXRlcmxpbWl0PSIxMCIgc3Ryb2tlLXdpZHRoPSIyIi8+PC9zdmc+',
            'none' => '',
        ];

        $html = '';
        $html .= '<ul class="list-group border-0">';

        if (!empty($dirs)) {
            foreach ($dirs as $dir) {

                $img = $assoc['none'];

                if ($dir['isDir'] === true) {
                    $img = $assoc['folder'];
                }

                $html .= '<li class="list-group-item border-top-1 border-right-0 border-left-0"'
                    . 'data-fullPath="' . $dir['fullPath'] . '" '
                    . 'data-ext="' . $dir['ext'] . '" '
                    . 'data-readable="' . $dir['readable'] . '" '
                    . 'data-writeable="' . $dir['writeable'] . '" '
                    . 'data-isDir="' . $dir['isDir'] . '" '
                    . 'data-name="' . $dir["name"] . '">'
                    . '<span class="btn btn-sm btn-danger float-left removeBtn" onclick="listDelete(this);"><i class="fa fa-trash-alt"></i></span>'
                    . '<img class="icon" src="' . $img . '">'
                    . '<span onclick="listSelect(this);" class="btn btn-sm text-left ' . ($dir['writeable'] == true ?: 'text-muted') . '">'
                    . $dir["name"]
                    . '</span>'
                    . '</li>';
            }
        }

        $html .= '</ul>';

        echo $html;
        exit;
    }

    /**
     * Read file action
     */
    if ($action === 'file') {
        $file = $_POST["path"];
        $result = $editor->getFile($file);
        echo json_encode(['status' => (bool) $result['status'], 'content' => $result['content'] ?: '', 'path' => $file, 'msg' => $result['msg']]);
        exit;
    }

    /**
     * Create dir action
     */
    if ($action === 'createDir') {
        $current = !empty($_POST["current"]) ? $_POST["current"] : $editor->rootPath . '/';
        $dir = $current . '/' . $_POST["dir"];
        $result = $editor->createDir($dir);
        echo json_encode(['status' => (bool) $result['status'], 'path' => $dir, 'msg' => $result['msg']]);
        exit;
    }

    /**
     * Create file action
     */
    if ($action === 'createFile') {
        $current = !empty($_POST["current"]) ? $_POST["current"] : $editor->rootPath . '/';
        $content = $_POST["content"];
        $file = $current . '/' . $_POST["file"];
        $result = $editor->createFile($file, $content);
        echo json_encode(['status' => (bool) $result['status'], 'path' => $file, 'msg' => $result['msg']]);
        exit;
    }

    /**
     * Save file action
     */
    if ($action === 'saveFile') {
        $content = $_POST["content"];
        $file = $_POST["file"];
        $result = $editor->saveFile($file, $content);
        echo json_encode(['status' => (bool) $result['status'], 'path' => $file, 'msg' => $result['msg']]);
        exit;
    }

    /**
     * Delete folder action
     */
    if ($action === 'deleteDir') {
        $path = $_POST['path'];

        if ($path == '.' || $path == '..' || $path == '/.' || $path == '/..') {
            echo json_encode(['status' => (bool) false, 'path' => $path, 'msg' => 'Burada silme işlemi gerçekleştiremezsiniz']);
            exit;
        }

        $editor->deleteDirAction($path);
        $status = is_dir($path) ? true : false;
        $msg = ($status == true ? 'Klasör Silinemedi : ' : 'Klasör Silindi : ') . $path;
        echo json_encode(['status' => (bool) $status, 'path' => $path, 'msg' => $msg]);
        exit;
    }

    /**
     * Delete file action
     */
    if ($action === 'deleteFile') {
        $path = $_POST['path'];
        $result = $editor->deleteFileAction($path);
        echo json_encode(['status' => (bool) $result['status'], 'path' => $path, 'msg' => $result['msg']]);
        exit;
    }

    /**
     * File upload action
     */
    if ($action === 'uploadFiles') {

        $dir = $_POST['dir'];

        $errors = null;

        if (!$editor->checkRoot($dir)) {
            echo json_encode([0 => ['status' => (bool) false, 'msg' => 'Yükleme Yoluna İzin Verilmemektedir.', 'filename' => 'Hata', 'path' => $dir]]);
            exit;
        }

        if (!is_dir($dir)) {
            echo json_encode([0 => ['status' => (bool) false, 'msg' => 'Hedef Klasör Bulunamadı', 'path' => $dir, 'filename' => 'Hata', 'size' => 0]]);
            exit;
        }

        $result = null;
        $input = 'file';

        if (empty($_FILES[$input]['name'][0])) {
            echo json_encode([0 => ['status' => (bool) false, 'msg' => 'Dosya seçilmedi', 'path' => $dir, 'filename' => 'Hata']]);
            exit;
        }

        foreach ($_FILES[$input]['name'] as $key => $val) {

            $image_name = basename($_FILES[$input]['name'][$key]);
            $tmp_name = $_FILES[$input]['tmp_name'][$key];
            $size = $_FILES[$input]['size'][$key];

            $path = $dir . DIRECTORY_SEPARATOR . $image_name;

            if ($_FILES[$input]['error'][$key] !== UPLOAD_ERR_OK) {
                $result[$key] = ['status' => (bool) false, 'msg' => 'Dosya Yüklenemedi (UPLOAD_ERR_OK)', 'path' => $path, 'filename' => $image_name, 'size' => $size];
                continue;
            }

            if (!$editor->checkExtension($image_name)) {
                $result[$key] = ['status' => (bool) false, 'msg' => 'İzin Verilmeyen Dosya Uzantısı', 'path' => $path, 'filename' => $image_name, 'size' => $size];
                continue;
            }

            $moveRes = move_uploaded_file($tmp_name, $path);

            if ($moveRes === false) {
                $result[$key] = ['status' => (bool) false, 'msg' => 'Dosya Yüklenemedi (move_uploaded_file)' . $moveRes, 'path' => $path, 'filename' => $image_name, 'size' => $size];
                continue;
            }

            if ($moveRes === true) {
                $result[$key] = ['status' => (bool) true, 'msg' => 'Dosya yüklendi', 'path' => $path, 'filename' => $image_name, 'size' => $size];
                continue;
            }
        }

        echo json_encode($result);
        exit;
    }

    /**
     * Download file action
     */
    if ($action === 'downloadFile') {

        $path = $_GET['path'];

        if (empty($path)) {
            die('Dosya Yolu Boş Olamaz');
        }

        if (!$editor->checkRoot($path)) {
            die('Bu Dosya Yoluna İzin Verilmemektedir  : ' . $path);
        }

        if (!$editor->checkExtension($path)) {
            die('İzin Verilmeyen Dosya Uzantısı : ' . $path);
        }

        if (!is_readable($path)) {
            die('Dosya Okunamadı : ' . $path);
        }

        header('Content-Type: application/octet-stream');
        header("Content-Transfer-Encoding: Binary");
        header("Content-disposition: attachment; filename=\"" . basename($path) . "\"");
        readfile($path);
        exit;
    }

} else {?>


    <!doctype html>
    <html lang="tr">
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

        <title>Editör</title>

        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css" integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">
        <script src="https://code.jquery.com/jquery-3.4.1.min.js"  crossorigin="anonymous"></script>
        <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous"></script>
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js" integrity="sha384-wfSDF2E50Y2D1uUdj0O3uMBJnjuUD4Ih7YwaYd1iqfktj0Uod8GCExl3Og8ifwB6" crossorigin="anonymous"></script>
        <link rel="stylesheet" href="//use.fontawesome.com/releases/v5.6.3/css/all.css" crossorigin="anonymous"/>

        <script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.4.7/ace.js" crossorigin="anonymous"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.4.7/ext-beautify.js" crossorigin="anonymous"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.4.7/ext-searchbox.js" crossorigin="anonymous"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.4.7/ext-modelist.js" crossorigin="anonymous"></script>

        <style>
            html,body,.h-100{
                height:100vh !important;
            }
            *{
                border-radius: 0!important;
            }
            .form-control:focus{
                border-color: #cccccc;
                -webkit-box-shadow: none;
                box-shadow: none;
            }
            #editor{
                border-left:0 !important;
                border-right:0 !important;
            }
            #dirList  li{
                padding: 0.1rem 0.1rem;
                margin-bottom: -1px;
                display: flex;
                justify-content: flex-start;
                align-items: center;
            }
            .removeBtn{
                padding: 0.1rem 0.3rem;
                font-size: 10px;
                margin-left: 5px;
            }
            #dirList .icon{
                height: 16px;
                width: 16px;
                margin-left: 10px;
            }
            .p-2custom{
                padding: 0 0.1rem !important;
            }
        </style>

    </head>

    <body class="bg-dark">

    <div class="container-fluid d-flex h-100 flex-column">

        <div class="row flex-fill d-flex justify-content-start">

            <div class="col-2 p-2custom h-100">
                <div class="card h-100 border-0">
                    <div class="card-block overflow-auto">
                        <div class="list-group-item border-top-1 border-right-0 border-left-0 bg-dark d-flex flex-row justify-content-between p-1">
                            <span onclick="goUp(this);" class="btn btn-sm text-light"><i class="fa fa-caret-up"></i> Üst Klasöre Git</span>
                            <span onclick="refreshSidebar();" class="btn btn-sm text-warning"><i class="fa fa-sync"></i> Yenile</span>
                        </div>
                        <div class="" id="dirList">Yükleniyor...</div>
                    </div>
                </div>
            </div>

            <div class="col p-2custom h-100">
                <div class="card h-100 border-0">
                    <div class="p-2 text-left">
                        <div class="d-block" id="current"></div>
                    </div>
                    <div id="editor" class="card-block form-control h-100"></div>
                    <div class="d-block p-2">
                        <div class="float-left text-left m-0 p-1">
                            <span id="statusText" class="badge badge-primary">Editör Başlatıldı</span>
                        </div>
                        <div class="float-right text-right">
                            <span data-toggle="modal" data-target="#uploadModal" class="btn btn-sm btn-primary" id="uploadBtn"><i class="fa fa-upload"></i> Yükle</span>
                            <span onclick="download(this);" class="btn btn-sm btn-primary disabled" disabled="disabled" id="downloadBtn"><i class="fa fa-download"></i> İndir</span>
                            <span onclick="newDir(this);" class="btn btn-sm btn-primary"><i class="fa fa-folder-plus"></i> Yeni Klasör</span>
                            <span onclick="newFile(this);" class="btn btn-sm btn-primary"><i class="fa fa-file-alt"></i> Yeni Dosya</span>
                            <span onclick="refreshFile(this);" class="btn btn-sm btn-success disabled" disabled="disabled" id="refreshFileBtn"><i class="fa fa-sync"></i> Yenile</span>
                            <span onclick="saveFile(this);" class="btn btn-sm btn-success disabled" disabled="disabled" id="saveBtn"><i class="fa fa-save"></i> Kaydet</span>
                            <span onclick="closeFile(this);" class="btn btn-sm btn-danger disabled" disabled="disabled" id="closeBtn"><i class="fa fa-window-close"></i> Kapat</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </div>

    <!-- Modal -->
    <div class="modal fade" id="uploadModal" tabindex="-1" role="dialog" aria-labelledby="uploadModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="uploadModalTitle">Dosya Yükleme</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="" method="POST" id="uploadForm">
                        <input type="hidden" name="action" value="uploadFiles"></input>
                        <input type="file" multiple="true" multiple name="file[]" class="form-control"/>
                        <button type="submit" class="btn btn-success mt-2 float-right"><i class="fa fa-upload"></i> Yükle</button>
                    </form>
                </div>
                <div class="modal-footer d-block p-1" id="uploadStatus"></div>
            </div>
        </div>
    </div>

    <script>

        const _BASE = '<?=$editor->rootPath?>';
        var _current = '<?=$editor->rootPath?>';

        var _file = '';
        var _changed = false;

        const editor = ace.edit("editor");
        editor.setOptions({
            autoScrollEditorIntoView: true
        });
        editor.resize();
        //editor.setTheme("ace/theme/monokai");
        editor.session.setMode("ace/mode/php");
        editor.getSession().setUseWorker(false);


        function setCurrent(path) {
            path = typeof path != 'undefined' && path != '' ? path : _BASE;
            _current = path;
            $('#current').html(path);
            localStorage.setItem("current", path);
        }

        function getCurrent() {
            return _current;
        }

        function setCurrentFile(file) {
            _file = file;
            localStorage.setItem("file", file);
        }

        function getCurrentFile() {
            return _file;
        }

        function setChanged(bool) {
            _changed = bool;
            localStorage.setItem("changed", bool);
        }

        function getChanged() {
            return _changed;
        }

        editor.getSession().on('change', function () {
            setChanged(true);
        });

        function setMode(filePath) {
            var modelist = ace.require("ace/ext/modelist");
            var mode = modelist.getModeForPath(filePath).mode;
            editor.session.setMode(mode);
        }

        function getDir(path) {
            $.ajax({
                url: '',
                data: {action: 'dir', path: path},
                type: 'POST',
                dataType: 'HTML',
                success: function (res) {
                    setCurrent(path);
                    $('#dirList').html(res);
                }
            });
        }

        function setStatus(text) {
            $('#statusText').html(text);
        }

        function switchSaveBtn(bool) {
            var btn = $('#saveBtn');
            if (bool === true) {
                btn.removeClass('disabled').removeAttr('disabled');
                return;
            }
            btn.addClass('disabled').attr('disabled', 'disabled');
        }

        function switchCloseBtn(bool) {
            var btn = $('#closeBtn');
            if (bool === true) {
                btn.removeClass('disabled').removeAttr('disabled');
                return;
            }
            btn.addClass('disabled').attr('disabled', 'disabled');
        }

        function switchDownloadBtn(bool) {
            var btn = $('#downloadBtn');
            if (bool === true) {
                btn.removeClass('disabled').removeAttr('disabled');
                return;
            }
            btn.addClass('disabled').attr('disabled', 'disabled');
        }

        function switchRefreshFileBtn(bool) {
            var btn = $('#refreshFileBtn');
            if (bool === true) {
                btn.removeClass('disabled').removeAttr('disabled');
                return;
            }
            btn.addClass('disabled').attr('disabled', 'disabled');
        }

        function getFile(path) {

            if (getChanged() === true) {
                var ask = confirm(path + ' Dosyasına geçiş yapmak istediğininize emin misiniz ? Bu dosyada yaptığınız değişiklikler kaydedilmedi.');
                if (ask == false) {
                    return false;
                }
            }

            $.ajax({
                url: '',
                data: {action: 'file', path: path},
                type: 'POST',
                dataType: 'JSON',
                success: function (res) {
                    setStatus(res.msg);
                    if (res.status == true) {
                        setCurrentFile(path);
                        setMode(path);
                        editor.setValue(res.content);
                        switchCloseBtn(true);
                        switchSaveBtn(true);
                        switchDownloadBtn(true);
                        switchRefreshFileBtn(true);
                        setChanged(false);
                    }
                }
            });
        }

        function listSelect(dom) {
            var _this = $(dom).parent();
            var _isDir = _this.attr('data-isDir') == 1 ? true : false;
            if (_isDir === true) {
                getDir(_this.attr('data-fullPath'));
                return;
            } else {
                getFile(_this.attr('data-fullPath'), _this.attr('data-ext'));
                return;
            }
        }

        function listDelete(dom) {
            var _this = $(dom).parent();
            var _path = _this.attr('data-fullPath');
            var _text = _this.attr('data-isDir') == true ? ' klasörü içindekiler ile birlikte silinsin mi ?' : ' dosyası silinsin mi ?';
            var _confirm = confirm(_path + _text);
            if (_confirm === false) {
                return false;
            }

            var _action = _this.attr('data-isDir') ? 'deleteDir' : 'deleteFile';
            if (_confirm === true) {
                $.ajax({
                    url: '',
                    data: {
                        action: _action,
                        path: _path
                    },
                    type: 'POST',
                    dataType: 'JSON',
                    success: function (res) {
                        setStatus(res.msg);
                        refreshSidebar();
                    }
                });
            }
        }

        function goUp(dom) {

            if (getCurrent() == _BASE) {
                return false;
            }

            var str = getCurrent();
            str = str.replace(_BASE, '');
            var arrVars = str.split("/");
            var lastVar = arrVars.pop();
            var restVar = arrVars.join("/");
            getDir(_BASE + restVar);
        }

        function newDir() {
            var directory = prompt("Oluşturmak istediğiniz klasörün adını yazınız. Klasör burada oluşturulacak : " + getCurrent());
            if (directory != '' && directory != null) {
                $.ajax({
                    url: '',
                    data: {
                        action: 'createDir',
                        current: getCurrent(),
                        dir: directory
                    },
                    type: 'POST',
                    dataType: 'JSON',
                    success: function (res) {
                        setStatus(res.msg);
                        refreshSidebar();
                    }
                });
            }
        }

        function newFile() {

            var filename = prompt("Geçerli Editördeki içerik kullanılarak oluşturmak istediğiniz dosyanın adını yazınız. Klasör burada oluşturulacak : " + getCurrent());
            if (filename != '' && filename != null) {
                $.ajax({
                    url: '',
                    data: {
                        action: 'createFile',
                        current: getCurrent(),
                        file: filename,
                        content: editor.getValue()
                    },
                    type: 'POST',
                    dataType: 'JSON',
                    success: function (res) {
                        setStatus(res.msg);
                        if (res.status === true) {
                            refreshSidebar();
                            setChanged(false);
                            getFile(getCurrent() + '/' + filename);
                        }
                    }
                });
            }
        }

        function saveFile(dom) {

            var _me = $(dom);
            if (_me.attr('disabled')) {
                return false;
            }

            if (getCurrentFile() == '') {
                return false;
            }

            if (getChanged() === false) {
                return false;
            }

            $.ajax({
                url: '',
                data: {
                    action: 'saveFile',
                    file: getCurrentFile(),
                    content: editor.getValue()
                },
                type: 'POST',
                dataType: 'JSON',
                success: function (res) {
                    if (res.status == true) {
                        setChanged(false);
                    }
                    setStatus(res.msg);
                }
            });
        }

        function closeFile(dom) {

            var _me = $(dom);
            if (_me.attr('disabled')) {
                return false;
            }

            if (getChanged() === true) {
                var ask = confirm(getCurrentFile() + ' dosyasını kapatmak istediğinize emin misiniz ? Bu dosyada yaptığınız değişiklikler kaydedilmedi.');
                if (ask == false) {
                    return false;
                }
            }

            setStatus('Dosya kapatıldı : ' + getCurrentFile());
            setCurrentFile('');
            editor.setValue('');
            switchCloseBtn(false);
            switchSaveBtn(false);
            switchDownloadBtn(false);
            switchRefreshFileBtn(false);
            setChanged(false);
        }

        function refreshSidebar() {
            getDir(getCurrent());
        }

        $(document).on('submit', '#uploadForm', function (e) {

            e.preventDefault();
            var me = $(this);
            var formData = new FormData(me[0]);
            formData.append('dir', getCurrent());
            var _anySuccess = false;
            $.ajax({
                type: 'POST',
                data: formData,
                dataType: 'JSON',
                url: '',
                cache: false,
                contentType: false,
                enctype: 'multipart/form-data',
                processData: false,
                success: function (res) {

                    $('#uploadStatus').html('');
                    $.each(res, function (k, v) {

                        if (v.status == true) {
                            _anySuccess = true;
                        }

                        var _status = v.status == true ? 'success' : 'danger';
                        var txt = '<span class="w-100 m-0 badge badge-' + _status + ' text-left">';
                        txt += v.filename + ' : ' + v.msg;
                        txt += '</span>';
                        $('#uploadStatus').append(txt);
                    });
                }, complete: function () {
                    if (_anySuccess) {
                        me[0].reset();
                        getDir(getCurrent());
                    }
                }
            });
        });
        function download(dom) {
            var _me = $(dom);
            if (_me.attr('disabled')) {
                return false;
            }
            window.open('?action=downloadFile&path=' + getCurrentFile(), '_blank');
        }

        function refreshFile(dom) {
            var _me = $(dom);
            if (_me.attr('disabled')) {
                return false;
            }
            getFile(getCurrentFile());
        }

        window.onbeforeunload = function ()
        {
            if (getChanged() === true) {
                return true;
            } else {
                /*Do Something*/
            }
        };


        function getFromLocal() {

            var _path = localStorage.getItem("current");
            var _file = localStorage.getItem("file");
            var _changed = localStorage.getItem("changed");

            if (_path) {
                getDir(_path);
            }

            if (_file) {
                getFile(_file);
            }

            if (_changed) {
                setChanged(_changed);
            }
        }

        /**
         * INIT
         */
        getDir('');
        getFromLocal();

    </script>

    </body>
</html>

    <?php }?>