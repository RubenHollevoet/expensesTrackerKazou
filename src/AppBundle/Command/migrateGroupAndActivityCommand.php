<?php
/**
 * Created by PhpStorm.
 * User: ruben.hollevoet
 * Date: 03/03/19
 * Time: 11:37
 */

namespace AppBundle\Command;


use AppBundle\Entity\Trip;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class migrateGroupAndActivityCommand extends Command
{
    protected static $defaultName = 'app:group:mi';
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * migrateGroupAndActivityCommand constructor.
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        Command::__construct();

        $this->entityManager = $entityManager;
    }

    protected function configure()
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('migrate existing groups and activities to new field on trips')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // outputs multiple lines to the console (adding "\n" at the end of each line)
        $output->writeln([
            'Starting migration groups and activities',
            '============',
        ]);

        /**
         * @var $em EntityManager
         */
        $em = $this->entityManager;
        $trips = $em->getRepository(Trip::class)->findAll();
        $count = 0;

        /**
         * @var $trip Trip
         */
        foreach ($trips as $trip) {
            if(!$trip->getActivityName()) {

                //todo
                $tripArr = [$trip->getGroup()->getName()];
                if($trip->getGroup()->getParentCount()) {
                    array_unshift($tripArr, $trip->getGroup()->getParent()->getName());

                    if($trip->getGroup()->getParent()->getParentCount()) {
                        array_unshift($tripArr, $trip->getGroup()->getParent()->getParent()->getName());
                    };
                }

                $trip->setGroupStack($tripArr);

//                $output->writeln('group stack: ' . implode($tripArr, ', '));

                $activity = $trip->getActivity()->getName();
//                $output->writeln('activity: ' . $activity);
                $trip->setActivityName($activity);

                $count ++;
//                $output->writeln('Parsed trip with ID ' . $trip->getId());

                if($count % 100 === 0) {
                    $output->writeln('Finish batch. Total count: ' . $count);
                    $em->flush();
                }
            }
            else {
                $output->writeln('Trip already parsed ' . $trip->getId());
            }


        }

        $em->flush();

        $count = 0;
        foreach ($trips as $trip) {
            if($trip->getActivity() || $trip->getGroup()) {
                $trip->setActivity(null);
                $trip->setGroup(null);
                $count++;
            }

            if($count % 100 === 1) {
                $output->writeln('Removed trip group and activity - ' . $count);
                $em->flush();
            }
        }
        $output->writeln('Removed trip group and activity finished - ' . $count);

        $em->flush();

        // outputs a message followed by a "\n"
        $output->writeln([
            '',
            'FINISHED PARSING - ' . $count . ' trips parsed',
            'TOTOAL TRIPS - ' . count($trips),
            '============',
        ]);

    }
}
