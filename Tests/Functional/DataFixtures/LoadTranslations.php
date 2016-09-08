<?php

namespace Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\TranslationBundle\Entity\Translation;

class LoadTranslations extends AbstractFixture
{
    const TRANSLATION1 = 'translation.trans1';
    const TRANSLATION2 = 'translation.trans2';
    const TRANSLATION3 = 'translation.trans3';

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->createTranslation($manager, self::TRANSLATION1, LoadLanguages::LANGUAGE1);
        $this->createTranslation($manager, self::TRANSLATION2, LoadLanguages::LANGUAGE1);
        $this->createTranslation($manager, self::TRANSLATION3, LoadLanguages::LANGUAGE2);

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param string $key
     * @param string $locale
     * @return Translation
     */
    protected function createTranslation(ObjectManager $manager, $key, $locale)
    {
        $translation = new Translation();
        $translation
            ->setDomain('test_domain')
            ->setKey($key)
            ->setValue($key)
            ->setLocale($locale);
        $manager->persist($translation);
        $this->addReference($key, $translation);

        return $translation;
    }
}
