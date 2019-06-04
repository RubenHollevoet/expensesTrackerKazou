<?php
/**
 * Created by PhpStorm.
 * User: ruben.hollevoet
 * Date: 03/03/19
 * Time: 11:37
 */

namespace AppBundle\Command;


use AppBundle\Entity\Region;
use AppBundle\Entity\Trip;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SyncCodesCommand extends Command
{
    protected static $defaultName = 'app:trip:sync';
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
        $this->setName('app:trip:sync');

        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('loop over all trips with missing codes. Try to retrieve them form the sheets')
            ->addArgument('regionId', InputArgument::REQUIRED, 'The id of the region.')
            ->addOption('force', null, InputOption::VALUE_OPTIONAL, 'persist data to DB', false)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $regionId = $input->getArgument('regionId');
        $region = $this->entityManager->getRepository(Region::class)->find($regionId);
        if(!$region) {
            $output->writeln(['no region found for id '.$regionId]);
            return;
        }

        // outputs multiple lines to the console (adding "\n" at the end of each line)
        $output->writeln([
            'Starting completing missing trip codes for region '.$region->getName(),
            '============',
        ]);

        $dryRun = $input->getOption('force') !== null;
        if($dryRun) {
            $output->writeln(['Run command with \'--force\' to persist data to the database.']);
        }

        /**
         * @var $em EntityManager
         */
        $em = $this->entityManager;
        $trips = $em->getRepository(Trip::class)->findUnpaidTripsForRegionWithoutCodes($regionId);

        $json = file_get_contents('https://script.google.com/macros/s/AKfycbwoHHF7gDJfsxw7hINO9bWCdXeARGxTUO4IVx9PsZUKc4y4rgk/exec?file='.$region->getGoogleSheetsKey());
        $obj = json_decode($json)->data;

        $updateCount = 0;

        /**
         * @var $trip Trip
         */
        foreach ($trips as $trip) {
            $updates = [];

            $groupStack = $trip->getGroupStack();
            if(count($groupStack) <= 2) {
                //this command is only designed for group stacks with 3 items
                continue;
            }

            $round1 = $groupStack[0];
            if(property_exists($obj, $round1))
            {
                $round1 = $obj->$round1;
            }
            else {
                $output->writeln(['no match found for trip with id '.$trip->getId().' during round 1. Searching for: '.$groupStack[0]]);
                continue;
            }

            $round2 = str_replace(' & ', '-', $groupStack[1]);
            if(property_exists($round1->children, $round2))
            {
                $round2 = $round1->children->$round2;
            }
            else {
                $output->writeln(['no match found for trip with id '.$trip->getId().' during round 2. Searching for: '.$groupStack[1]]);
                continue;
            }

            $round3 = $groupStack[2];
            if(property_exists($round2->children, $round3))
            {
                $round3 = $round2->children->$round3;
            }
            else {
                $output->writeln(['no match found for trip with id '.$trip->getId().' during round 3. Searching for: '.$groupStack[2]]);
                continue;
            }

            try {
                $details = $round3->details;

                //apply code vacation
                if(!$trip->getCodeVacation() && array_key_exists('code', $details) && $details->code) {
                    if(!$dryRun) $trip->setCodeVacation($details->code);
                    $updates[] = 'code vacation -> '.$details->code;
                }

                //apply code S2
                if(!$trip->getCodeS2() && array_key_exists('code_s2', $details) && $details->code_s2) {
                    if(!$dryRun) $trip->setCodeS2($details->code_s2);
                    $updates[] = 'S2 vacation -> '.$details->code_s2;
                }

                //apply code S3
                if(!$trip->getCodeS3() && array_key_exists('code_s3', $details) && $details->code_s3) {
                    if(!$dryRun) $trip->setCodeS3($details->code_s3);
                    $updates[] = 'S3 vacation -> '.$details->code_s3;
                }

                //apply code S5
                if(!$trip->getCodeS5() && array_key_exists('code_s5', $details) && $details->code_s5) {
                    if(!$dryRun) $trip->setCodeS5($details->code_s5);
                    $updates[] = 'S5 vacation -> '.$details->code_s5;
                }

                if($updates) {
                    $output->writeln(['Following updates occurred for trip '.$trip->getId().' ('.implode(' -> ', $trip->getGroupStack()).') : '.implode(', ', $updates)]);
                    $updateCount++;
                }
            }
            catch (\Exception $e) {
                $output->writeln([
                    'Error while parsing trip with ID' . $trip->getId(),
                    $e->getMessage()]);
            }
        }

        if(!$dryRun) $em->flush();

        $output->writeln('In total ' . $updateCount.' trips have been updated. Finished.');
        $output->writeln('Unable to find: '. (count($trips) - $updateCount));
    }
}
