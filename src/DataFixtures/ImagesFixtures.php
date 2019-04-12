<?php

declare(strict_types=1);

namespace Bolt\DataFixtures;

use Bolt\Configuration\Areas;
use Bolt\Configuration\Config;
use Bolt\Content\MediaFactory;
use Doctrine\Common\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;
use GuzzleHttp\Client;

class ImagesFixtures extends BaseFixture
{
    /** @var Generator */
    private $faker;

    private $urls = [];

    public const AMOUNT = 10;

    /** @var MediaFactory */
    private $mediaFactory;

    public function __construct(Config $config, Areas $areas, MediaFactory $mediaFactory)
    {
        $this->urls = [
            'https://source.unsplash.com/1280x1024/?business,workspace,interior/',
            'https://source.unsplash.com/1280x1024/?cityscape,landscape,nature/',
            'https://source.unsplash.com/1280x1024/?animal,kitten,puppy,cute/',
            'https://source.unsplash.com/1280x1024/?technology/',
        ];

        parent::__construct($config, $areas);
        $this->faker = Factory::create();
        $this->mediaFactory = $mediaFactory;
    }

    public function load(ObjectManager $manager): void
    {
        $this->fetchImages();
        $this->loadImages($manager);

        $manager->flush();
    }

    private function fetchImages(): void
    {
        $outputPath = $this->areas->get('files', 'basepath') . '/stock/';

        if (! is_dir($outputPath)) {
            mkdir($outputPath);
        }

        for ($i = 1; $i <= $this::AMOUNT; $i++) {
            $url = $this->urls[array_rand($this->urls)] . random_int(10000, 99999);
            $filename = 'image_' . random_int(10000, 99999) . '.jpg';

            $client = new Client();
            $resource = fopen($outputPath . $filename, 'w');
            $client->request('GET', $url, ['sink' => $resource]);
        }
    }

    private function loadImages(ObjectManager $manager): void
    {
        $path = $this->areas->get('files', 'basepath') . '/stock/';

        $index = $this->getImagesIndex($path);

        foreach ($index as $file) {
            $media = $this->mediaFactory->createOrUpdateMedia($file, 'files', $this->faker->sentence(6, true));
            $media->setAuthor($this->getRandomReference('user'))
                ->setDescription($this->faker->paragraphs(3, true))
                ->setCopyright('© Unsplash');

            $manager->persist($media);
        }

        $manager->flush();
    }
}
