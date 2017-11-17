<?php

class FileHandler
{
    private $config     = [];
    private $videos_ext = ".{avi,mp4,flv,webm,mkv}";
    private $musics_ext = ".{mp3,ogg,m4a,flac,opus}";

    public function __construct()
    {
        $this->config = require dirname(__DIR__) . '/config/config.php';
    }

    /**
     * Liste toutes les vidéos correspondant au pattern
     * @return array Array contenant les vidéos
     */
    public function listVideos()
    {
        $videos = [];

        if (!$this->outuput_folder_exists()) {
            return;
        }

        $folder = $this->get_downloads_folder() . '/';

        foreach (glob($folder . '*' . $this->videos_ext, GLOB_BRACE) as $file) {
            $video         = [];
            $video["name"] = str_replace($folder, "", $file);
            $video["size"] = $this->to_human_filesize(filesize($file));

            $videos[] = $video;
        }

        return (array)$videos;
    }

    /**
     * Liste toutes les musiques correspondant au pattern
     * @return array Array contenant les musiques
     */
    public function listMusics()
    {
        $musics = [];

        if (!$this->outuput_folder_exists()) {
            return;
        }

        $folder = $this->get_downloads_folder() . '/';

        foreach (glob($folder . '*' . $this->musics_ext, GLOB_BRACE) as $file) {
            $music         = [];
            $music["name"] = str_replace($folder, "", $file);
            $music["size"] = $this->to_human_filesize(filesize($file));

            $musics[] = $music;
        }

        return (array)$musics;
    }

    /**
     * Supprime un fichier
     * @param  int    $id   Numéro du fichier à supprimer
     * @param  string $type Catégorie du fichier à supprimer
     * @return void       
     */
    public function delete(int $id, string $type)
    {
        $folder = $this->get_downloads_folder() . '/';
        $i      = 0;

        if ($type === 'v') {
            $exts = $this->videos_ext;
        } elseif ($type === 'm') {
            $exts = $this->musics_ext;
        } else {
            return;
        }

        foreach (glob($folder . '*' . $exts, GLOB_BRACE) as $file) {
            if ($i == $id) {
                unlink($file);
            }
            $i++;
        }
    }

    /**
     * Vérifications de base
     * @return boolean Folder existe ou pas (Et si on arrive à le créer)
     */
    private function outuput_folder_exists()
    {
        if (!is_dir($this->get_downloads_folder())) {
            if (!mkdir($this->get_downloads_folder(), 0777)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Convertit la taille d'un fichier en une valeur compréhensible
     * @param  integer $bytes    Taille du fichier (en bytes)
     * @param  integer $decimals Nombre de décimales souhaitées
     * @return string            Taille du fichier compréhensible
     */
    public function to_human_filesize($bytes, $decimals = 0)
    {
        $sz     = 'BKMGTP';
        $factor = floor((strlen($bytes) - 1) / 3);
        return (string)sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor];
    }

    /**
     * Connaitre son espace libre
     * @return string Espace libre
     * Return 0 si le folder de downloads n'existe pas
     */
    public function free_space()
    {
        return (string)$this->to_human_filesize(disk_free_space($this->get_downloads_folder()));
    }

    /**
     * Chemin absolu du dossier de downloads
     * @return string Path du folder de downloads
     */
    public function get_downloads_folder()
    {
        $path = $this->config["outputFolder"];
        if (strpos($path, "/") !== 0) {
            $path = dirname(__DIR__) . '/' . $path;
        }
        return (string)$path;
    }
}
