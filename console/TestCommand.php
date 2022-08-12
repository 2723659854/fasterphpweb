<?php
namespace console;
require dirname(__DIR__).'/vendor/autoload.php';
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
class TestCommand extends Command{
    protected function configure()
    {
        $this->setName('fuck')
            ->setDescription('Creates new users.')
            ->setHelp("This command allows you to create users...")
        ;
        $this->addArgument('name', InputArgument::REQUIRED, 'name');
        $this->addOption('age', 'd', InputOption::VALUE_NONE, '模式');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->write('input your name.');
        $name= $input->getArgument('name');
        $option=$input->getOption('age');
        $output->write('fuck you!');
        $output->write($name.'-'.$option);
        return 0;
    }
}





