<?php

declare(strict_types=1);

namespace SimpleFileManager;

/**
 * Özel istisna sınıfları, hata yönetimini daha anlaşılır kılar.
 */
class FileManagerException extends \Exception {}
class PermissionDeniedException extends FileManagerException {}
class InvalidPathException extends FileManagerException {}
class FileOperationException extends FileManagerException {}

/**
 * @author Uğur Gülay <ugur.gulay@tsoft.com.tr>
 * Simple File Editor - Modernized and Secured
 */
class Editor
{
    /**
     * İzin verilen dosya uzantıları.
     * @var string[]
     */
    private const ALLOWED_EXTENSIONS = [
        'php', 'sh', 'bs', 'bash', 'c',
        'html', 'shtml', 'css', 'js', 'xml',
        'conf', 'config', 'ini', 'txt', 'licence', 'license',
        'csv', 'tsv', 'doc', 'docx', 'zip',
        'rar', 'gz', 'sql', 'xls', 'xlsx', 'json',
    ];

    /**
     * Çalışma dizininin çözümlenmiş, mutlak yolu.
     * Bu özellik değiştirilemez (readonly).
     */
    private readonly string $realRootPath;

    /**
     * Editor constructor.
     *
     * @param string $rootPath Sınıfın çalışacağı kök dizin.
     * @throws InvalidPathException Sağlanan kök dizin geçerli değilse fırlatılır.
     */
    public function __construct(public readonly string $rootPath)
    {
        $realPath = realpath($this->rootPath);
        if ($realPath === false || !is_dir($realPath)) {
            throw new InvalidPathException("Sağlanan kök dizin geçersiz veya mevcut değil: {$this->rootPath}");
        }
        $this->realRootPath = $realPath;
    }

    /**
     * Belirtilen dizinin içeriğini listeler.
     *
     * @param string|null $path Listelenecek dizin. Boş bırakılırsa kök dizin kullanılır.
     * @return array Dizin içeriği hakkında bilgi içeren bir dizi.
     * @throws PermissionDeniedException İzin verilmeyen bir dizine erişilmeye çalışılırsa.
     */
    public function getDir(?string $path = null): array
    {
        $currentPath = $path ?? $this->rootPath;
        $this->assertPathIsWithinRoot($currentPath);

        $result = [];
        $iterator = new \FilesystemIterator(
            $currentPath,
            \FilesystemIterator::KEY_AS_PATHNAME | \FilesystemIterator::CURRENT_AS_FILEINFO | \FilesystemIterator::SKIP_DOTS
        );

        /** @var \SplFileInfo $fileInfo */
        foreach ($iterator as $fileInfo) {
            $isDir = $fileInfo->isDir();

            if (!$isDir && !$this->isExtensionAllowed($fileInfo->getFilename())) {
                continue;
            }

            $result[] = [
                'dir' => $currentPath,
                'name' => $fileInfo->getFilename(),
                'fullPath' => $fileInfo->getRealPath(),
                'ext' => $isDir ? '' : strtolower($fileInfo->getExtension()),
                'isDir' => $isDir,
                'readable' => $fileInfo->isReadable(),
                'writeable' => $fileInfo->isWritable(),
            ];
        }

        // Sonuçları sırala: Önce klasörler, sonra dosyalar, her ikisi de kendi içinde alfabetik.
        usort($result, function ($a, $b) {
            if ($a['isDir'] !== $b['isDir']) {
                return $a['isDir'] ? -1 : 1;
            }
            return strnatcasecmp($a['name'], $b['name']);
        });

        return $result;
    }

    /**
     * Bir dosyanın içeriğini döndürür.
     *
     * @param string $filePath Okunacak dosyanın yolu.
     * @return string Dosyanın içeriği.
     * @throws PermissionDeniedException İzin verilmeyen bir dosyaya erişilmeye çalışılırsa.
     * @throws FileOperationException Dosya okunamıyorsa veya izin verilmeyen bir uzantıya sahipse.
     */
    public function getFile(string $filePath): string
    {
        $this->assertPathIsWithinRoot($filePath);

        if (!is_file($filePath) || !is_readable($filePath)) {
            throw new FileOperationException("Dosya mevcut değil veya okuma izni yok: {$filePath}");
        }

        if (!$this->isExtensionAllowed($filePath)) {
            throw new FileOperationException("Bu dosya uzantısını okuma izni yok: {$filePath}");
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new FileOperationException("Dosya içeriği okunamadı: {$filePath}");
        }

        return $content;
    }

    /**
     * Yeni bir klasör oluşturur.
     *
     * @param string $dirPath Oluşturulacak klasörün yolu.
     * @throws PermissionDeniedException İzin verilmeyen bir konumda klasör oluşturulmaya çalışılırsa.
     * @throws FileOperationException Klasör zaten mevcutsa veya oluşturulamıyorsa.
     */
    public function createDir(string $dirPath): void
    {
        $this->assertPathIsWithinRoot($dirPath);

        if (file_exists($dirPath)) {
            throw new FileOperationException("Klasör veya dosya zaten mevcut: {$dirPath}");
        }

        if (!mkdir($dirPath, 0755, true)) {
            throw new FileOperationException("Klasör oluşturulamadı: {$dirPath}");
        }
    }

    /**
     * Bir dosyayı kaydeder. Dosya yoksa oluşturur, varsa üzerine yazar.
     *
     * @param string $filePath Kaydedilecek dosyanın yolu.
     * @param string $content Dosyanın yeni içeriği.
     * @throws PermissionDeniedException İzin verilmeyen bir konuma dosya kaydedilmeye çalışılırsa.
     * @throws FileOperationException Uzantı yasaklıysa veya yazma işlemi başarısız olursa.
     */
    public function saveFile(string $filePath, string $content): void
    {
        $this->assertPathIsWithinRoot($filePath);

        if (!$this->isExtensionAllowed($filePath)) {
            throw new FileOperationException("Bu dosya uzantısına yazma izni yok: {$filePath}");
        }

        // Dosya mevcut değilse, üst dizinin yazılabilir olduğunu kontrol et
        if (!file_exists($filePath) && !is_writable(dirname($filePath))) {
             throw new FileOperationException("Dizine yazma izni yok: " . dirname($filePath));
        }

        // Dosya mevcut ve yazılamıyorsa
        if (file_exists($filePath) && !is_writable($filePath)) {
            throw new FileOperationException("Dosyaya yazma izni yok: {$filePath}");
        }

        if (file_put_contents($filePath, $content) === false) {
            throw new FileOperationException("Dosya kaydedilemedi: {$filePath}");
        }
        
        // Yeni oluşturulan dosya için izinleri ayarla
        if (!file_exists($filePath)) {
            chmod($filePath, 0664);
        }
    }

    /**
     * Belirtilen dosya veya klasörü (içeriğiyle birlikte) siler.
     *
     * @param string $path Silinecek dosya veya klasörün yolu.
     * @throws PermissionDeniedException İzin verilmeyen bir şeyi silmeye çalışırsa.
     * @throws InvalidPathException Kök dizinin kendisi silinmeye çalışılırsa.
     * @throws FileOperationException Silme işlemi başarısız olursa.
     */
    public function delete(string $path): void
    {
        $this->assertPathIsWithinRoot($path);

        $realPath = realpath($path);
        if ($realPath === $this->realRootPath) {
            throw new InvalidPathException("Güvenlik nedeniyle kök dizin silinemez.");
        }
        
        if (!file_exists($path)) {
            return; // Zaten yok, işlem başarılı sayılır.
        }

        if (is_file($path) || is_link($path)) {
            if (!unlink($path)) {
                throw new FileOperationException("Dosya silinemedi: {$path}");
            }
        } elseif (is_dir($path)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            
            /** @var \SplFileInfo $fileInfo */
            foreach ($iterator as $fileInfo) {
                if ($fileInfo->isDir()) {
                    if (!rmdir($fileInfo->getRealPath())) {
                         throw new FileOperationException("Alt klasör silinemedi: {$fileInfo->getRealPath()}");
                    }
                } else {
                    if (!unlink($fileInfo->getRealPath())) {
                        throw new FileOperationException("Alt dosya silinemedi: {$fileInfo->getRealPath()}");
                    }
                }
            }
            if (!rmdir($path)) {
                throw new FileOperationException("Ana klasör silinemedi: {$path}");
            }
        }
    }
    
    /**
     * View dosyasını render eder ve içeriğini string olarak döndürür.
     */
    public function render(): string
    {
        ob_start();
        require __DIR__ . '/view.php';
        return ob_get_clean();
    }
    
    /**
     * Bir yolun, tanımlı kök dizin içinde olup olmadığını güvenli bir şekilde kontrol eder.
     * Path Traversal saldırılarını önler.
     *
     * @throws PermissionDeniedException Yol, kök dizin dışında ise fırlatılır.
     */
    private function assertPathIsWithinRoot(string $path): void
    {
        $realTargetPath = realpath($path);

        // Hedef yol henüz mevcut değilse (oluşturma işlemleri için), ebeveyn dizini kontrol et.
        if ($realTargetPath === false) {
            $realTargetPath = realpath(dirname($path));
        }

        if ($realTargetPath === false || !str_starts_with($realTargetPath, $this->realRootPath)) {
            throw new PermissionDeniedException("Ana dizinin dışına erişim yetkiniz yok: {$path}");
        }
    }

    /**
     * Bir dosya yolunun uzantısının izin verilenler listesinde olup olmadığını kontrol eder.
     */
    private function isExtensionAllowed(string $filepath): bool
    {
        $extension = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
        return in_array($extension, self::ALLOWED_EXTENSIONS, true);
    }
}
