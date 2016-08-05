<?php

namespace TypiCMS\Modules\Core\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use League\Flysystem\Adapter\Local as LocalAdapter;
use League\Flysystem\Filesystem as Flysystem;
use League\Flysystem\MountManager;

class Extend extends Command
{
    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'admintool:extend {module : The module that you want to extend}
            {--force : Overwrite any existing files.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Move a module from the vendor directory to the /Modules directory.';

    /**
     * Current Module.
     *
     * @var string
     */
    protected $module;


    /**
     * Create a new key generator command.
     *
     * @param \Illuminate\Filesystem\Filesystem $files
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $this->module = ucfirst($this->argument('module'));
        if (!is_dir(base_path('vendor/webfactorybulgaria/'.strtolower($this->module)))) {
            throw new Exception('Module “'.$this->module.'” not found in vendor directory.');
        }
        $provider = 'TypiCMS\Modules\\'.$this->module.'\Providers\ModuleProvider';
        if (class_exists($provider)) {
            $this->publishModule();
            $this->rebuildBaseClasses();
            $this->publishModule('/Custom');
            $this->buildCustomClasses();
        } else {
            throw new Exception($provider.' not found, did you add it to config/app.php?');
        }
    }

    /**
     * Publishes the module.
     *
     * @param string $subdir
     *
     * @return mixed
     */
    private function publishModule($subdir = '')
    {
        $from = base_path('vendor/webfactorybulgaria/'.strtolower($this->module).'/src');
        $to = base_path('Modules/'.$this->module . $subdir);

        if ($this->files->isDirectory($from)) {
            $this->publishDirectory($from, $to);
        } else {
            $this->error("Can’t locate path: <{$from}>");
        }

        $this->info('Publishing complete for module ['.$this->module.']!');
    }

    /**
     * Publish the directory to the given directory.
     *
     * @param string $from
     * @param string $to
     *
     * @return void
     */
    protected function publishDirectory($from, $to)
    {
        $manager = new MountManager([
            'from' => new Flysystem(new LocalAdapter($from)),
            'to'   => new Flysystem(new LocalAdapter($to)),
        ]);

        foreach ($manager->listContents('from://', true) as $file) {
            $path = $file['path'];
            if (substr($path, 0, 8) === 'database' || substr($path, 0, 9) === 'resources' || substr($path, 0, 6) === 'config') {
                continue;
            }
            if ($file['type'] === 'file' && (!$manager->has('to://'.$file['path']) || $this->option('force'))) {
                $manager->put('to://'.$file['path'], $manager->read('from://'.$file['path']));
            }
        }

        $this->status($from, $to, 'Directory');
    }

    /**
     * Search and remplace all occurences of 
     * TypiCMS\Modules\<Module> to TypiCMS\Modules\<Module>\Custom
     */
    public function rebuildBaseClasses()
    {
        $directory = base_path('Modules/'.$this->module);

        $manager = new MountManager([
            'directory' => new Flysystem(new LocalAdapter($directory)),
        ]);

        foreach ($manager->listContents('directory://', true) as $file) {
            if ($file['type'] === 'file') {
                // Replace references to include Custom
                $content = preg_replace('|TypiCMS\\\\Modules\\\\([\w]+)|', "TypiCMS\\\\Modules\\\\$1\\\\Custom", $manager->read('directory://'.$file['path']));
                $manager->put('directory://'.$file['path'], $content);

                // Replace namespaces back to normal
                $content = preg_replace('|namespace[ ]+TypiCMS\\\\Modules\\\\' . $this->module . '\\\\Custom|', 'namespace TypiCMS\\\\Modules\\\\' . $this->module, $manager->read('directory://'.$file['path']));
                $manager->put('directory://'.$file['path'], $content);

            }
        }
    }


    /**
     * Search and remplace all occurences of 
     * TypiCMS\Modules\<Module> to TypiCMS\Modules\<Module>\Custom
     */
    public function buildCustomClasses()
    {
        $directory = base_path('Modules/'.$this->module . '/Custom');

        $manager = new MountManager([
            'directory' => new Flysystem(new LocalAdapter($directory)),
        ]);

        foreach ($manager->listContents('directory://', true) as $file) {
            if ($file['type'] === 'file') {

                $source = $manager->read('directory://'.$file['path']);

                $matches = [];
                if (preg_match('|namespace[ ]+(.*);|', $source, $matches)) {
                    $namespace = preg_replace('|TypiCMS\\\\Modules\\\\([\w]+)|', "TypiCMS\\\\Modules\\\\$1\\\\Custom", $matches[1]);

                    $baseNamespace = str_replace('\Custom', '', $namespace);
                    
                    if (preg_match('/(class|interface|trait) +(\w+)(.*)?/', $source, $matches)) {
                        $structureType = $matches[1];
                        $classname = $matches[2];

                        $implementsUseLine = '';
                        $implements = '';
                        if ($additional = $matches[2]) {
                            if (preg_match('|implements +(\w+)|', $additional, $matches)) {
                                //dd($matches);
                                $implements = $matches[1];
                                if ($implements) {
                                    if (preg_match('|use[ ]+(.*)' . preg_quote($implements) . ';|', $source, $matches)) {
                                        // there is use clause for the implemented interface
                                        if (preg_match('|TypiCMS|', $matches[1])) {
                                            $implementsUseLine = preg_replace('|TypiCMS\\\\Modules\\\\([\w]+)|', "TypiCMS\\\\Modules\\\\$1\\\\Custom", $matches[0]);
                                        } else {
                                            $implementsUseLine = $matches[0];
                                        }
                                    }
                                }
                            }
                        }

$content = '<?php

namespace '.$namespace.';

use '.$baseNamespace.'\\'.$classname.' as Base;
'.($implementsUseLine ? $implementsUseLine . "\n" : '').'
'.$structureType.' ' . $classname .' extends Base' . ($implements ? ' implements ' . $implements : '') . '
{

}

';

                        $manager->put('directory://'.$file['path'], $content);
                    }
                }
            }
        }
    }

    /**
     * Write a status message to the console.
     *
     * @param string $from
     * @param string $to
     * @param string $type
     *
     * @return void
     */
    protected function status($from, $to, $type)
    {
        $from = str_replace(base_path(), '', realpath($from));

        $to = str_replace(base_path(), '', realpath($to));

        $this->line('<info>Copied '.$type.'</info> <comment>['.$from.']</comment> <info>To</info> <comment>['.$to.']</comment>');
    }

}
