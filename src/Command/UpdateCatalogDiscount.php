<?php
namespace App\Command;

use App\Entity\Channel\ChannelPricing;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateCatalogDiscount extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'chullanka:update:discount';
    
    protected $manager;
    protected $output;
    
    public function __construct(EntityManagerInterface $manager)
    {
        parent::__construct();
        
        $this->manager = $manager;
    }
    
    protected function configure(): void
    {
        $this
            ->setDescription('Màj des prix spéciaux')
        ;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;
        $output->writeln([
            '',
            '=================',
            'Chullanka - Discount',
            '=================',
            '',
        ]);

        $now = new \DateTime();

        // récupération des attributs
        /*$pricings = $this->manager->getRepository(ChannelPricing::class)->findBy([
            'discount_price' => 'NOT NULL'
        ]);*/
        $qb = $this->manager->getRepository(ChannelPricing::class)->createQueryBuilder('cp');
        $pricings = $qb->select()
            ->where($qb->expr()->isNotNull('cp.discountPrice'))
            ->andWhere($qb->expr()->isNotNull('cp.discountFrom'))
            ->andWhere($qb->expr()->isNotNull('cp.discountTo'))
            ->getQuery()->getResult();
        foreach($pricings as $pricing)
        {
            echo $pricing->getId().' : '.$pricing->getDiscountPrice()."\n";

            if(!empty($pricing->getDiscountPrice())
            && !empty($pricing->getDiscountFrom())
            && !empty($pricing->getDiscountTo())
            && ($now >= $pricing->getDiscountFrom())
            && ($now < $pricing->getDiscountTo())
            )
            {
                $pricing->setPrice( $pricing->getDiscountPrice() );
            }
            else
            {
                $pricing->setPrice( $pricing->getOriginalPrice() );
            }

            $this->manager->persist($pricing);
        }

        $this->manager->flush();
        return Command::SUCCESS;
    }
}