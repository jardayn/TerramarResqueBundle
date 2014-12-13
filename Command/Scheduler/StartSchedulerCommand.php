<?php

namespace Terramar\Bundle\ResqueBundle\Command\Scheduler;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class StartSchedulerCommand extends AbstractSchedulerCommand
{
    protected function configure()
    {
        $this
            ->setName('resque:scheduler:start')
            ->setDescription('Start the resque scheduler')
            ->addOption('interval', 'i', InputOption::VALUE_REQUIRED, 'How often to check for new jobs across the queues', 5)
            ->addOption('foreground', 'f', InputOption::VALUE_NONE, 'Should the scheduler run in foreground')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Force creation of a new worker if the PID file exists')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $pidFile = $this->getPidFilename();
        if (file_exists($pidFile) && !$input->getOption('force')) {
            throw new \Exception('PID file exists - use --force to override');
        }

        if (file_exists($pidFile)) {
            unlink($pidFile);
        }
        
        $env = array(
            'APP_INCLUDE' => $this->getContainer()->getParameter('kernel.root_dir').'/bootstrap.php.cache',
            'VERBOSE'     => 1,
            'INTERVAL'    => $input->getOption('interval'),
        );
        $prefix = $this->getContainer()->getParameter('terramar.resque.prefix');
        if (!empty($prefix)) {
            $env['PREFIX'] = $prefix;
        }
        if ($input->getOption('verbose')) {
            $env['VVERBOSE'] = 1;
        }
        if ($input->getOption('quiet')) {
            unset($env['VERBOSE']);
        }

        $redisHost = $this->getContainer()->getParameter('terramar.resque.redis.host');
        $redisPort = $this->getContainer()->getParameter('terramar.resque.redis.port');
        $redisDatabase = $this->getContainer()->getParameter('terramar.resque.redis.database');
        if ($redisHost != null && $redisPort != null) {
            $backend = strpos($redisHost, 'unix:') === false ? $redisHost.':'.$redisPort : $redisHost;

            $env['REDIS_BACKEND'] = $backend;
        }
        if (isset($redisDatabase)) {
            $env['REDIS_BACKEND_DB'] = $redisDatabase;
        }

        $workerCommand = strtr('%bin% %dir%/../bin/resque-scheduler', array(
                '%bin%' => $this->getPhpBinary(),
                '%dir%' => $this->getContainer()->getParameter('kernel.root_dir'),
            ));

        if (!$input->getOption('foreground')) {
            $logFile = $this->getContainer()->getParameter('kernel.logs_dir') 
                . '/resque-scheduler_' 
                . $this->getContainer()->getParameter('kernel.environment') 
                . '.log';
            $workerCommand = strtr('nohup %cmd% > %log_file% 2>&1 & echo $!', array(
                    '%cmd%'      => $workerCommand,
                    '%log_file%' => $logFile
                ));
        }

        // In windows: When you pass an environment to CMD it replaces the old environment
        // That means we create a lot of problems with respect to user accounts and missing vars
        // this is a workaround where we add the vars to the existing environment.
        if (defined('PHP_WINDOWS_VERSION_BUILD')) {
            foreach ($env as $key => $value) {
                putenv($key."=". $value);
            }
            $env = null;
        }

        $process = new Process($workerCommand, null, $env, null, null);

        $output->writeln(\sprintf('Starting worker <info>%s</info>', $process->getCommandLine()));

        if ($input->getOption('foreground')) {
            $process->run(function ($type, $buffer) use ($output) {
                    $output->write($buffer);
                });
        }
        // else we recompose and display the worker id
        else {
            $process->run();
            $pid = \trim($process->getOutput());
            file_put_contents($pidFile, $pid);
            
            if (!$input->getOption('quiet')) {
                $hostname = function_exists('gethostname') ? gethostname() : php_uname('n');
                
                $output->writeln(\sprintf('<info>Scheduler started</info> %s:%s', $hostname, $pid));
            }
        }
    }

    private function getPhpBinary()
    {
        $finder = new PhpExecutableFinder();

        return $finder->find();
    }
}
