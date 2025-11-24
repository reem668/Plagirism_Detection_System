<?php
namespace Helpers;

class FileStorage {
    public static function store($file, $allowed=['pdf','doc','docx','txt'], $max=10485760){
        if(!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            throw new \Exception('Upload error');
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if(!in_array($ext, $allowed)) throw new \Exception('Invalid file type');

        if($file['size'] > $max) throw new \Exception('File too large');

        $dir = __DIR__ . '/../../storage/uploads';
        if(!is_dir($dir)) mkdir($dir, 0755, true);

        $safe = time().'_'.bin2hex(random_bytes(6)).'.'.$ext;
        $dest = $dir.'/'.$safe;

        if(!move_uploaded_file($file['tmp_name'], $dest)) throw new \Exception('File move failed');

        return [
            'path' => $dest,
            'stored' => $safe,
            'orig' => $file['name'],
            'size' => $file['size']
        ];
    }
}
