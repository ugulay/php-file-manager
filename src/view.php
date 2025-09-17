<?php

declare(strict_types=1);

namespace SimpleFileManager;

// Bu satırı, Editor.php dosyasını autoload ile yüklemiyorsanız aktif edin.
// require_once __DIR__ . '/Editor.php';

/**
 * =================================================================
 * AYARLAR
 * =================================================================
 * Lütfen çalışma dizinini burada belirtin.
 * Bu, dosya yöneticisinin erişebileceği en üst seviye klasördür.
 * GÜVENLİK İÇİN BU DİZİNİN DIŞINA ÇIKILAMAZ.
 */
const WORKING_DIR = __DIR__; // Örnek: '/var/www/html/uploads' veya projenizin kök dizini

/**
 * Yardımcı Fonksiyon: Standart bir JSON yanıtı oluşturur ve betiği sonlandırır.
 */
function sendJsonResponse(array $data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

/**
 * =================================================================
 * İSTEK YÖNETİCİSİ (CONTROLLER)
 * =================================================================
 * Gelen AJAX isteklerini yakalar ve Editor sınıfını kullanarak işlem yapar.
 */
try {
    $editor = new \SimpleFileManager\Editor(WORKING_DIR);
    $action = $_POST['action'] ?? $_GET['action'] ?? null;

    if ($action !== null) {
        ob_clean(); // Olası çıktıları temizle

        switch ($action) {
            case 'dir':
                $path = $_POST['path'] ?? null;
                $dirs = $editor->getDir($path);

                // Bu aksiyon HTML döndürdüğü için özel olarak işleniyor.
                $folderIcon = 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiA/PjwhRE9DVFlQRSBzdmcgIFBVQkxJQyAnLS8vVzNDLy9EVEQgU1ZHIDEuMS8vRU4nICAnaHR0cDovL3d3dy53My5vcmcvR3JhcGhpY3MvU1ZHLzEuMS9EVEQvc3ZnMTEuZHRkJz48c3ZnIGVuYWJsZS1iYWNrZ3JvdW5kPSJuZXcgMCAwIDUwIDUwIiBoZWlnaHQ9IjUwcHgiIGlkPSJMYXllcl8xIiB2ZXJzaW9uPSIxLjEiIHZpZXdCb3g9IjAgMCA1MCA1MCIgd2lkdGg9IjUwcHgiIHhtbDpzcGFjZT0icHJlc2VydmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6eGxpbms9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkveGxpbmsiPjxyZWN0IGZpbGw9Im5vbmUiIGhlaWdodD0iNTAiIHdpZHRoPSI1MCIvPjxwYXRoIGQ9Ik00NiwxNXYtNCAgYzAtMS4xMDQtMC44OTYtMi0yLTJjMCwwLTI0LjY0OCwwLTI2LDBjLTEuNDY5LDAtMi40ODQtNC00LTRIM0MxLjg5Niw1LDEsNS44OTYsMSw3djR2Mjl2NGMwLDEuMTA0LDAuODk2LDIsMiwyaDM5ICBjMS4xMDQsMCwyLTAuODk2LDItMiIgZmlsbD0ibm9uZSIgc3Ryb2tlPSIjMDAwMDAwIiBzdHJva2UtbGluZWNhcD0icm91bmQiIHN0cm9rZS1taXRlcmxpbWl0PSIxMCIgc3Ryb2tlLXdpZHRoPSIyIi8+PHBhdGggZD0iTTEsNDRsNS0yNyAgYzAtMS4xMDQsMC44OTYtMiwyLTJoMzljMS4xMDQsMCwyLDAuODk2LDIsMmwtNSwyNyIgZmlsbD0ibm9uZSIgc3Ryb2tlPSIjMDAwMDAwIiBzdHJva2UtbGluZWNhcD0icm91bmQiIHN0cm9rZS1taXRlcmxpbWl0PSIxMCIgc3Ryb2tlLXdpZHRoPSIyIi8+PC9zdmc+';
                $html = '<ul class="list-group border-0">';
                foreach ($dirs as $item) {
                    $icon = $item['isDir'] ? $folderIcon : '';
                    $writeClass = $item['writeable'] ? '' : 'text-muted';
                    $html .= sprintf(
                        '<li class="list-group-item border-top-1 border-right-0 border-left-0" data-fullPath="%s" data-ext="%s" data-isDir="%d" data-name="%s">
                            <span class="btn btn-sm btn-danger float-left removeBtn" onclick="listDelete(this);"><i class="fa fa-trash-alt"></i></span>
                            <img class="icon" src="%s">
                            <span onclick="listSelect(this);" class="btn btn-sm text-left %s">%s</span>
                        </li>',
                        htmlspecialchars($item['fullPath']),
                        htmlspecialchars($item['ext']),
                        (int)$item['isDir'],
                        htmlspecialchars($item['name']),
                        $icon,
                        $writeClass,
                        htmlspecialchars($item['name'])
                    );
                }
                $html .= '</ul>';
                echo $html;
                exit;

            case 'file':
                $path = $_POST['path'] ?? '';
                $content = $editor->getFile($path);
                sendJsonResponse(['status' => true, 'content' => $content, 'path' => $path, 'msg' => 'Dosya okundu: ' . basename($path)]);
                break;

            case 'createDir':
                $current = $_POST['current'] ?? $editor->rootPath;
                $newDir = $_POST['dir'] ?? '';
                $path = rtrim($current, '/\\') . DIRECTORY_SEPARATOR . $newDir;
                $editor->createDir($path);
                sendJsonResponse(['status' => true, 'msg' => 'Klasör oluşturuldu: ' . $newDir]);
                break;

            case 'createFile': // saveFile ile aynı mantıkta çalışacak
            case 'saveFile':
                $content = $_POST['content'] ?? '';
                $path = $_POST['file'] ?? '';
                // createFile için path oluşturma
                if ($action === 'createFile') {
                    $current = $_POST['current'] ?? $editor->rootPath;
                    $path = rtrim($current, '/\\') . DIRECTORY_SEPARATOR . $path;
                }
                $editor->saveFile($path, $content);
                sendJsonResponse(['status' => true, 'path' => $path, 'msg' => 'Dosya kaydedildi: ' . basename($path)]);
                break;

            case 'deleteDir': // deleteFile ile aynı mantıkta çalışacak
            case 'deleteFile':
                $path = $_POST['path'] ?? '';
                if (in_array(basename($path), ['.', '..'])) {
                    throw new \Exception('Bu konumda silme işlemi yapılamaz.');
                }
                $editor->delete($path);
                sendJsonResponse(['status' => true, 'msg' => 'Silindi: ' . basename($path)]);
                break;

            case 'uploadFiles':
                $dir = $_POST['dir'] ?? $editor->rootPath;
                $files = $_FILES['file'] ?? [];
                $results = [];

                if (empty($files['name'][0])) {
                     throw new FileOperationException('Yüklenecek dosya seçilmedi.');
                }

                foreach ($files['name'] as $key => $name) {
                    $filePath = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . basename($name);
                    try {
                        if ($files['error'][$key] !== UPLOAD_ERR_OK) {
                            throw new FileOperationException('Dosya yükleme hatası oluştu (Hata Kodu: ' . $files['error'][$key] . ').');
                        }
                        $content = file_get_contents($files['tmp_name'][$key]);
                        if ($content === false) {
                            throw new FileOperationException('Geçici dosya okunamadı.');
                        }
                        $editor->saveFile($filePath, $content);
                        $results[] = ['status' => true, 'msg' => 'Yüklendi', 'filename' => $name];
                    } catch (\Exception $e) {
                        $results[] = ['status' => false, 'msg' => $e->getMessage(), 'filename' => $name];
                    }
                }
                sendJsonResponse($results);
                break;

            case 'downloadFile':
                $path = $_GET['path'] ?? '';
                $content = $editor->getFile($path); // getFile tüm güvenlik kontrollerini yapar
                header('Content-Type: application/octet-stream');
                header("Content-Transfer-Encoding: Binary");
                header("Content-disposition: attachment; filename=\"" . basename($path) . "\"");
                header('Content-Length: ' . strlen($content));
                echo $content;
                exit;

            default:
                sendJsonResponse(['status' => false, 'msg' => 'Geçersiz bir aksiyon çalıştırıldı.'], 400);
                break;
        }
    }
} catch (\Exception $e) {
    // Editor sınıfından veya başka bir yerden fırlatılan tüm hataları yakala
    sendJsonResponse(['status' => false, 'msg' => $e->getMessage()], 500);
}

/**
 * =================================================================
 * GÖRÜNÜM (VIEW)
 * =================================================================
 * Yukarıdaki PHP bloğu bir aksiyonla çalışıp çıkmadıysa, bu HTML arayüzü oluşturulur.
 */
?>
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
                    <input type="file" multiple="true" name="file[]" class="form-control"/>
                    <button type="submit" class="btn btn-success mt-2 float-right"><i class="fa fa-upload"></i> Yükle</button>
                </form>
            </div>
            <div class="modal-footer d-block p-1" id="uploadStatus"></div>
        </div>
    </div>
</div>

<script>
    // PHP tarafından güvenli bir şekilde belirlenen kök dizin
    const _BASE = '<?= htmlspecialchars($editor->rootPath, ENT_QUOTES, 'UTF-8') ?>';
    let _current = '<?= htmlspecialchars($editor->rootPath, ENT_QUOTES, 'UTF-8') ?>';
    let _file = '';
    let _changed = false;

    const editor = ace.edit("editor");
    editor.setOptions({
        autoScrollEditorIntoView: true
    });
    editor.resize();
    //editor.setTheme("ace/theme/monokai");
    editor.session.setMode("ace/mode/php");
    editor.getSession().setUseWorker(false);

    // Genel AJAX hata yönetimi
    $(document).ajaxError(function(event, jqxhr, settings, thrownError) {
        let msg = 'Bilinmeyen bir sunucu hatası oluştu.';
        if (jqxhr.responseJSON && jqxhr.responseJSON.msg) {
            msg = jqxhr.responseJSON.msg;
        } else if (jqxhr.responseText) {
            // JSON parse edilemezse, ham metni göster (debug için)
            // msg = jqxhr.responseText;
        }
        setStatus(msg, 'danger');
    });

    function setCurrent(path) {
        path = typeof path != 'undefined' && path != '' ? path : _BASE;
        _current = path;
        $('#current').text(path); // .html() yerine .text() daha güvenli
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
        // localStorage.setItem("changed", bool); // Sayfa yenileme uyarısı için bu gereksiz
    }

    function getChanged() {
        return _changed;
    }

    editor.getSession().on('change', function () {
        setChanged(true);
    });

    function setMode(filePath) {
        const modelist = ace.require("ace/ext/modelist");
        const mode = modelist.getModeForPath(filePath).mode;
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
            },
            // Hata durumu zaten global ajaxError ile yakalanıyor.
        });
    }

    function setStatus(text, type = 'primary') {
        $('#statusText').html(text).removeClass().addClass('badge badge-' + type);
    }

    function toggleButton(selector, enable) {
        const btn = $(selector);
        if (enable) {
            btn.removeClass('disabled').removeAttr('disabled');
        } else {
            btn.addClass('disabled').attr('disabled', 'disabled');
        }
    }

    function getFile(path) {
        if (getChanged() === true) {
            if (!confirm('Mevcut dosyadaki değişiklikler kaydedilmedi. Yine de devam etmek istiyor musunuz?')) {
                return false;
            }
        }

        $.ajax({
            url: '',
            data: {action: 'file', path: path},
            type: 'POST',
            dataType: 'JSON',
            success: function (res) {
                if (res.status == true) {
                    setStatus(res.msg, 'success');
                    setCurrentFile(path);
                    setMode(path);
                    editor.setValue(res.content, -1); // -1 imleci başa alır
                    toggleButton('#closeBtn', true);
                    toggleButton('#saveBtn', true);
                    toggleButton('#downloadBtn', true);
                    toggleButton('#refreshFileBtn', true);
                    setChanged(false);
                } else {
                    setStatus(res.msg, 'danger');
                }
            }
        });
    }

    function listSelect(dom) {
        const _this = $(dom).parent();
        const isDir = _this.data('isdir') == 1;
        if (isDir) {
            getDir(_this.data('fullpath'));
        } else {
            getFile(_this.data('fullpath'));
        }
    }

    function listDelete(dom) {
        const _this = $(dom).parent();
        const path = _this.data('fullpath');
        const text = _this.data('isdir') == 1 ? ' klasörü içindekiler ile birlikte silinsin mi?' : ' dosyası silinsin mi?';

        if (!confirm(path + text)) {
            return false;
        }

        const action = _this.data('isdir') ? 'deleteDir' : 'deleteFile';
        $.ajax({
            url: '',
            data: { action: action, path: path },
            type: 'POST',
            dataType: 'JSON',
            success: function (res) {
                setStatus(res.msg, res.status ? 'success' : 'danger');
                if (res.status) {
                    refreshSidebar();
                }
            }
        });
    }

    function goUp() {
        if (getCurrent() === _BASE) {
            setStatus('Zaten en üst dizindesiniz.', 'warning');
            return false;
        }

        let currentPath = getCurrent();
        let parentPath = currentPath.substring(0, currentPath.lastIndexOf('/'));
        // Eğer sonuç boşsa (kök dizinin bir altındaysak) veya BASE'den kısaysa, BASE'e dön.
        if (parentPath === '' || parentPath.length < _BASE.length) {
            parentPath = _BASE;
        }
        getDir(parentPath);
    }

    function newDir() {
        const directory = prompt("Oluşturulacak klasör adı:", "yeni-klasor");
        if (directory) {
            $.ajax({
                url: '',
                data: { action: 'createDir', current: getCurrent(), dir: directory },
                type: 'POST',
                dataType: 'JSON',
                success: function (res) {
                    setStatus(res.msg, res.status ? 'success' : 'danger');
                    if (res.status) refreshSidebar();
                }
            });
        }
    }

    function newFile() {
        const filename = prompt("Oluşturulacak dosya adı:", "yeni-dosya.txt");
        if (filename) {
            $.ajax({
                url: '',
                data: { action: 'createFile', current: getCurrent(), file: filename, content: editor.getValue() },
                type: 'POST',
                dataType: 'JSON',
                success: function (res) {
                    setStatus(res.msg, res.status ? 'success' : 'danger');
                    if (res.status === true) {
                        refreshSidebar();
                        setChanged(false);
                        getFile(res.path);
                    }
                }
            });
        }
    }

    function saveFile(dom) {
        if ($(dom).hasClass('disabled') || !getCurrentFile() || !getChanged()) {
            return false;
        }

        $.ajax({
            url: '',
            data: { action: 'saveFile', file: getCurrentFile(), content: editor.getValue() },
            type: 'POST',
            dataType: 'JSON',
            success: function (res) {
                setStatus(res.msg, res.status ? 'success' : 'danger');
                if (res.status) {
                    setChanged(false);
                }
            }
        });
    }

    function closeFile(dom) {
        if ($(dom).hasClass('disabled')) {
            return false;
        }

        if (getChanged() && !confirm('Değişiklikler kaydedilmedi. Dosyayı yine de kapatmak istiyor musunuz?')) {
            return false;
        }

        setStatus('Dosya kapatıldı: ' + getCurrentFile(), 'info');
        setCurrentFile('');
        editor.setValue('', -1);
        toggleButton('#closeBtn', false);
        toggleButton('#saveBtn', false);
        toggleButton('#downloadBtn', false);
        toggleButton('#refreshFileBtn', false);
        setChanged(false);
    }

    function refreshSidebar() {
        getDir(getCurrent());
    }

    $('#uploadForm').on('submit', function (e) {
        e.preventDefault();
        const me = $(this);
        const formData = new FormData(me[0]);
        formData.append('dir', getCurrent());
        let anySuccess = false;

        $.ajax({
            type: 'POST',
            data: formData,
            dataType: 'JSON',
            url: '',
            cache: false,
            contentType: false,
            processData: false,
            success: function (res) {
                $('#uploadStatus').html('');
                res.forEach(v => {
                    if (v.status) anySuccess = true;
                    const statusClass = v.status ? 'success' : 'danger';
                    const txt = `<span class="w-100 m-0 badge badge-${statusClass} text-left">${v.filename}: ${v.msg}</span>`;
                    $('#uploadStatus').append(txt);
                });
            },
            complete: function () {
                if (anySuccess) {
                    me[0].reset();
                    refreshSidebar();
                }
            }
        });
    });

    function download(dom) {
        if ($(dom).hasClass('disabled')) {
            return false;
        }
        window.open('?action=downloadFile&path=' + encodeURIComponent(getCurrentFile()), '_blank');
    }

    function refreshFile(dom) {
        if ($(dom).hasClass('disabled')) {
            return false;
        }
        getFile(getCurrentFile());
    }

    window.onbeforeunload = function () {
        if (getChanged()) {
            return "Sayfadan ayrılırsanız kaydedilmemiş değişiklikler kaybolacaktır.";
        }
    };

    function getFromLocal() {
        const path = localStorage.getItem("current");
        const file = localStorage.getItem("file");

        getDir(path || _BASE);

        if (file) {
            // Dosya var mı diye kontrol etmeden açmak yerine bekle
            // getDir tamamlandığında dosyanın varlığını kontrol edip açmak daha mantıklı
            // Şimdilik basit tutalım:
            getFile(file);
        }
    }

    /**
     * INIT
     */
    $(document).ready(function() {
        getFromLocal();
    });

</script>

</body>
</html>
