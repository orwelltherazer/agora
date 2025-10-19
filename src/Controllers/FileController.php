<?php

namespace Agora\Controllers;

use Agora\Services\Database;

class FileController
{
    private $db;

    public function __construct(Database $database)
    {
        $this->db = $database;
    }

    public function serve(?string $year, ?string $month, ?string $filename): void
    {
        $filePath = __DIR__ . '/../../storage/uploads/' . $year . '/' . $month . '/' . $filename;

        if (!file_exists($filePath)) {
            http_response_code(404);
            echo "File not found";
            return;
        }

        // Déterminer le type MIME
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $filePath);
        finfo_close($finfo);

        // Envoyer les headers
        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: public, max-age=31536000');

        // Envoyer le fichier
        readfile($filePath);
        exit;
    }
}
