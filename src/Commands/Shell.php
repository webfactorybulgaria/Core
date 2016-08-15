<?php

namespace TypiCMS\Modules\Core\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use League\Flysystem\Adapter\Local as LocalAdapter;
use League\Flysystem\Filesystem as Flysystem;
use League\Flysystem\MountManager;

class Shell extends Command
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
    protected $signature = 'admintool:shell {module : The module that you want to shell}
            {--force : Overwrite any existing files.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates shell classes and modifies the module classes. Copies the files to the /Modules directory.';

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
        $this->publishModule();
        $this->rebuildBaseClasses();
        $this->publishModule('/Shells');
        $this->buildShellClasses();
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
     * TypiCMS\Modules\<Module> to TypiCMS\Modules\<Module>\Shells
     */
    public function rebuildBaseClasses()
    {
        $directory = base_path('Modules/'.$this->module);

        $manager = new MountManager([
            'directory' => new Flysystem(new LocalAdapter($directory)),
        ]);

        foreach ($manager->listContents('directory://', true) as $file) {
            if ($file['type'] === 'file') {
                // Replace references to include Shells
                $content = preg_replace('|TypiCMS\\\\Modules\\\\([\w]+)|', "TypiCMS\\\\Modules\\\\$1\\\\Shells", $manager->read('directory://'.$file['path']));
                $manager->put('directory://'.$file['path'], $content);

                // Replace namespaces back to normal
                $content = preg_replace('|namespace[ ]+TypiCMS\\\\Modules\\\\' . $this->module . '\\\\Shells|', 'namespace TypiCMS\\\\Modules\\\\' . $this->module, $manager->read('directory://'.$file['path']));
                $manager->put('directory://'.$file['path'], $content);

            }
        }
    }


    /**
     * Search and remplace all occurences of 
     * TypiCMS\Modules\<Module> to TypiCMS\Modules\<Module>\Shells
     */
    public function buildShellClasses()
    {
        $directory = base_path('Modules/'.$this->module . '/Shells');

        $manager = new MountManager([
            'directory' => new Flysystem(new LocalAdapter($directory)),
        ]);

        foreach ($manager->listContents('directory://', true) as $file) {
            if ($file['type'] === 'file') {

                $source = $manager->read('directory://'.$file['path']);

                $matches = [];
                if (preg_match('|namespace[ ]+(.*);|', $source, $matches)) {
                    $namespace = preg_replace('|TypiCMS\\\\Modules\\\\([\w]+)|', "TypiCMS\\\\Modules\\\\$1\\\\Shells", $matches[1]);

                    $baseNamespace = str_replace('\Shells', '', $namespace);
                    
                    if (preg_match('/(class|interface|trait) +(\w+)(.*)?/', $source, $matches)) {
                        $structureType = $matches[1];
                        $classname = $matches[2];

                        $implementsUseLine = '';
                        $implements = '';
                        if ($additional = $matches[3]) {
                            if (preg_match('|implements +(\w+)|', $additional, $matches)) {
                                //dd($matches);
                                $implements = $matches[1];
                                if ($implements) {
                                    if (preg_match('|use[ ]+(.*)' . preg_quote($implements) . ';|', $source, $matches)) {
                                        // there is use clause for the implemented interface
                                        if (preg_match('|TypiCMS|', $matches[1])) {
                                            $implementsUseLine = preg_replace('|TypiCMS\\\\Modules\\\\([\w]+)|', "TypiCMS\\\\Modules\\\\$1\\\\Shells", $matches[0]);
                                        } else {
                                            $implementsUseLine = $matches[0];
                                        }
                                    }
                                }
                            }
                        }

if ($structureType == 'trait'){
$content = '<?php

namespace '.$namespace.';

use '.$baseNamespace.'\\'.$classname.' as BaseTrait;

'.$structureType.' ' . $classname .'
{
    use BaseTrait;
}
';

} else {
$content = '<?php

namespace '.$namespace.';

use '.$baseNamespace.'\\'.$classname.' as Base'.ucfirst($structureType).';
'.($implementsUseLine ? $implementsUseLine . "\n" : '').'
'.$structureType.' ' . $classname .' extends Base'.ucfirst($structureType).'' . ($implements ? ' implements ' . $implements : '') . '
{

}
';
    
}

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
