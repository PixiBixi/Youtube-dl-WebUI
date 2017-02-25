<?php
include_once 'FileHandler.php';

class Downloader
{
    /**
     * URL des fichiers à télécharger
     * @var array
     */
    private $urls = [];

    /**
     * Config contenant config.php
     * @var array
     */
    private $config = [];

    /**
     * Download audio only
     * @var boolean
     */
    private $audio_only = false;

    /**
     * Tableau pour contenir les éventuelles erreurs
     * @var array
     */
    private $errors = [];

    /**
     * Chemin de download
     * @var string
     */
    private $download_path = "";

    /**
     * Constructeur de la classe Downloader
     * @param array   $post       Array contenant les fichiers à DL
     * @param boolean $audio_only Download only audio
     */
    public function __construct($post, $audio_only)
    {
        $this->config = require dirname(__DIR__) . '/config/config.php';

        $this->download_path = (new FileHandler())->get_downloads_folder();

        $this->audio_only = $audio_only;
        $this->urls       = explode(",", $post);

        if (!$this->check_requirements($audio_only)) {
            return;
        }

        foreach ($this->urls as $url) {
            if (!$this->is_valid_url($url)) {
                $this->errors[] = "\"" . $url . "\" is not a valid url !";
            }
        }

        if (isset($this->errors) && count($this->errors) > 0) {
            $_SESSION['errors'] = $this->errors;
            return;
        }

        if ($this->config["max_dl"] == 0) {
            $this->do_download();
        } elseif ($this->config["max_dl"] > 0) {
            if ($this->background_jobs() >= 0 && $this->background_jobs() < $this->config["max_dl"]) {
                $this->do_download();
            } else {
                $this->errors[] = "Simultaneous downloads limit reached !";
            }
        }

        if (isset($this->errors) && count($this->errors) > 0) {
            $_SESSION['errors'] = $this->errors;
            return;
        }
    }

    /**
     * Liste tous les backgrounds jobs
     * @return mix 0 si le shell_exec ne se fait pas, le nombre de process sinon
     */
    public static function background_jobs()
    {
        return shell_exec("ps aux | grep -v grep | grep -v \"youtube-dl -U\" | grep youtube-dl | wc -l");
    }

    /**
     * Nombre de backgrounds jobs
     * @return int Nombre de backgrounds jobs
     */
    public static function max_background_jobs()
    {
        $config = require dirname(__DIR__) . '/config/config.php';
        return $config["max_dl"];
    }

    /**
     * Liste les backgrounds jobs
     * @return array Array avec tous les details
     */
    public static function get_current_background_jobs()
    {
        exec("ps -A -o user,pid,etime,cmd | grep -v grep | grep -v \"youtube-dl -U\" | grep youtube-dl", $output);

        $bjs = [];

        if (count($output) > 0) {
            foreach ($output as $line) {
                $line  = explode(' ', preg_replace("/ +/", " ", $line), 4);
                $bjs[] = array(
                    'user' => $line[0],
                    'pid'  => $line[1],
                    'time' => $line[2],
                    'cmd'  => $line[3],
                );
            }

            return $bjs;
        } else {
            return null;
        }
    }

    /**
     * Kill tous les processus
     * @return mix false si marche pas, l'id si ça marche
     */
    public static function kill_them_all()
    {
        exec("ps -A -o pid,cmd | grep -v grep | grep youtube-dl | awk '{print $1}'", $output);

        if (count($output) <= 0) {
            return;
        }

        foreach ($output as $p) {
            shell_exec("kill " . $p);
        }

        $config = require dirname(__DIR__) . '/config/config.php';
        $folder = $this->download_path;

        foreach (glob($folder . '*.part') as $file) {
            unlink($file);
        }
    }

    /**
     * Requierments requis pour utiliser cette WebUI
     * @param  boolean $audio_only Boolean afin de savoir si l'on doit avoir un extracteur installé
     * @return boolean             Boolean pour voir s'il y a des erreurs ou pas
     */
    private function check_requirements($audio_only)
    {
        if ($this->is_youtubedl_installed() != 0) {
            $this->errors[] = "Youtube-dl is not installed, see <a href=\"https://rg3.github.io/youtube-dl/download.html\">here</a> !";
        }

        $this->check_outuput_folder();

        if ($audio_only) {
            if ($this->is_extracter_installed() != 0) {
                $this->errors[] = "Install an audio extracter (ex: avconv) !";
            }
        }

        if (isset($this->errors) && count($this->errors) > 0) {
            $_SESSION['errors'] = $this->errors;
            return false;
        }

        return true;
    }

    /**
     * Permet de savoir si youtube-dl est dans la variable $PATH
     * @return boolean YoutubeDL installé ou pas
     */
    private function is_youtubedl_installed()
    {
        exec("which youtube-dl", $out, $r);
        if(empty($out)) {
            return 1;
        } else {
            return 0;
        }
    }

    /**
     * Permet de savoir si l'extracteur configuré est installé
     * @return boolean Si l'extrateur est installé ou pas
     */
    private function is_extracter_installed()
    {
        exec("which " . $this->config["extracter"], $out, $r);
        return $r;
    }

    /**
     * Pour voir si l'URL est valide ou pas
     * @param  string  $url URL
     * @return boolean      URL valide ou pas
     */
    private function is_valid_url($url)
    {
        return filter_var(trim($url), FILTER_VALIDATE_URL);
    }

    /**
     * Pour savoir si le folder d'output est existant/si on peut écrire
     * @return void
     */
    private function check_outuput_folder()
    {
        if (!is_dir($this->download_path)) {
            if (!mkdir($this->download_path, 0775)) {
                $this->errors[] = "Output folder doesn't exist and creation failed !";
            }
        } else {
            if (!is_writable($this->download_path)) {
                $this->errors[] = "Output folder isn't writable !";
            }
        }
    }

    /**
     * Pour download une vidéo
     * @return void
     */
    private function do_download()
    {
        $cmd = "youtube-dl";
        $cmd .= " -o " . $this->download_path . "/";
        $cmd .= escapeshellarg("%(title)s-%(uploader)s.%(ext)s");

        if ($this->audio_only) {
            $cmd .= " -x --extract-audio --audio-format mp3";
        }

        foreach ($this->urls as $url) {
            $cmd .= " " . escapeshellarg($url);
        }

        $cmd .= " --restrict-filenames"; // --restrict-filenames is for specials chars
        $cmd .= " > /dev/null & echo $!";

        shell_exec($cmd);
    }
}
