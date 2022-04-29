<?php
namespace App\Command;

use App\Entity\Order\Order;
use App\Service\IzyproHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class IzyproCommand extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'chullanka:izypro:updatestates';
    
    protected $manager;
    protected $izyproHelper;
    protected $output;
    
    public function __construct(EntityManagerInterface $manager, IzyproHelper $izyproHelper)
    {
        parent::__construct();
        
        $this->manager = $manager;
        $this->izyproHelper = $izyproHelper;
    }
    
    protected function configure(): void
    {
        $this
            ->setDescription('Gestion du changement de statut des commandes via Izypro')
            ->setHelp('Récupère les fichiers de changements de statuts des commandes chez Izypro')
        ;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;
        $output->writeln([
            '',
            '=================',
            'Izypro - UpdateState',
            '=================',
            '',
        ]);
        
        $order = $this->manager->getRepository(Order::class)->find(37);
        if($this->izyproHelper->export($order)) $output->writeln('C good');

        //if(!$this->izyproHelper->updateOrderStates()) $output->writeln('PB SFTP');
        
        
        //$this->izyproHelper->changeOrderInStoreState(19, 'in_preparation');
        
        //$this->manager->flush();
        return Command::SUCCESS;
    }

    
}