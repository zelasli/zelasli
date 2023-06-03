<?php

/**
 * Loader class implementation responsible for including framework and libs
 * files
 * 
 * @since 0.0.7
 * @author Rufai Limantawa
 */
class Loader
{
    /**
     * Loaded files absolute path included using the Loader class using
     * namespace an __autoload() implementation
     * 
     * @var array
     */
    protected ?array $files = null;

    /**
     * Loader singleton instance
     * 
     * @var Loader
     */
    protected static ?Loader $instance = null;
    
    protected array $packages;

    /**
     * Check if the loader is registered to implement __autoload() 
     * implementation
     * 
     * @var bool
     */
    protected ?bool $isRegistered = null;

    protected function __construct()
    {
        $this->files = [];
        $this->packages = [];
        $this->isRegistered = false;
    }

    /**
     * Registered function as __autoload() implementation
     * 
     * @param string $class
     * @param bool $is_import
     * 
     * @return bool
     * 
     */
    public function autoload_register($class, $is_import = false)
    {
        $file_info = $this->get_file_from_namespace($class, $is_import);

        if (!empty($file_info['name'])) {
            return $this->include($file_info, $is_import);
        }
    }

    /**
     * Initialize Loader and boot it up.
     * @return Loader
     */
    public static function boot()
    {
        if (!self::$instance) {
            self::$instance = new Loader();
        }
        
        return self::$instance;
    }

    /**
     * Include file
     * 
     * @param array $file array of info about file
     * @param bool $is_import include type true on using import and namespace 
     * otherwise.
     * 
     * @return bool
     */
    public function include($file, $is_import = true)
    {
        if (
            is_array($file) && 
            is_file(@$file['path'])
        ) {
            include_once $file['path'];

            $using = ($is_import) ? "import" : "namespace";
            $this->files[$file['path']] = [
                $file,
                $using
            ];


            return true;
        }

        return false;
    }

    /**
     * Include __index file
     * 
     * @param string $package
     */
    public function import($package = null) {
        $this->autoload_register($package, true);
    }

    /**
     * Parse namespace to array of file info
     * 
     * @param string classNamespace
     * @param bool $is_import
     * 
     * @return array
     */
    protected function get_file_from_namespace($classNamespace, $is_import = false)
    {
        $path = "";
        $file = "";
        $returned = [
            "name" => "",
            "path" => "",
            "dirname" => "",
            "namespace" => "",
            "bucket" => ""
        ];

        foreach ($this->packages as $initial => $path) {
            if (strpos($classNamespace, $initial) === 0) {
                $path = $path . 
                DIRECTORY_SEPARATOR . 
                substr(
                    str_replace(
                        "\\", 
                        '/', 
                        ltrim($classNamespace, "/\\")
                    ), 
                    strlen($initial)
                );

                if (
                    ($is_import && is_file($check_file = $path . ".php")) || 
                    !$is_import
                ) {
                    if ($is_import) {
                        $file = substr($check_file, strrpos($check_file, '/'));
                    } elseif (is_file($check_file = $path . ".php")) {
                        $file = $check_file;
                    }
                } elseif (
                    $is_import && 
                    is_file($check_file = rtrim($path, '/') . DIRECTORY_SEPARATOR . "__index.php")
                ) {
                    $file = $check_file;
                }
                
                if (is_file($file)) {
                    $returned = [
                        'name' => ltrim(
                            substr($file, strrpos($file, '/')), 
                            '/'
                        ),
                        'path' => $file,
                        'dirname' => substr(
                            $file, 
                            0, 
                            strrpos($file, '/')
                        ),
                        'namespace' => $classNamespace,
                        'bucket'    => rtrim($initial, "/\\"),
                    ];
                }
            }
        }

        return $returned;
    }

    /**
     * Register Installed buckets namespace
     * 
     * If the $start parameter is true when installed buckets is registered
     * the loader will be registered as function to spl_autoload_register.
     * 
     * @param bool $start true on immediately register spl autoloader false 
     * otherwise
     * 
     * @return void
     */
    public function register($start = true)
    {
        if ($this->isRegistered) {
            trigger_error("Loader is already registered", E_USER_NOTICE);

            return;
        }

        if ($start) {
            $this->start();
        }

        $this->isRegistered = true;
    }

    /**
     * Register a new bucket
     * 
     * @param array $bucket_name
     * 
     * @return void
     */
    public function register_bucket($bucket_name, $path)
    {
        $this->packages[$bucket_name] = $path;
    }

    /**
     * Start autoload regsiter
     * 
     * @return bool true on success false on failure
     */
    public function start()
    {
        return spl_autoload_register([$this, "autoload_register"]);
    }
    
    /**
     * Stop autoload register
     * 
     * @return bool true on success false on failure
     */
    public function stop()
    {
        return spl_autoload_unregister([$this, "autoload_register"]);
    }
}
