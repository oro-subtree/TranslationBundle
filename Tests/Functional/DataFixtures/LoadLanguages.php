<?php

namespace Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Migrations\Data\Demo\ORM\LoadTranslationUsers;
use Oro\Bundle\UserBundle\Entity\User;

class LoadLanguages extends AbstractFixture implements DependentFixtureInterface
{
    const LANGUAGE1 = 'en_CA';
    const LANGUAGE2 = 'fr_FR';
    const LANGUAGE3 = 'en_US';

    /**
     * @var array
     */
    protected $languages = [
        self::LANGUAGE1 => [
        ],
        self::LANGUAGE2 => [
            'enabled' => true,
        ],
        self::LANGUAGE3 => [
            'enabled' => true,
            'user' => LoadTranslationUsers::TRANSLATOR_USERNAME,
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [LoadTranslationUsers::class];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->languages as $language => $definition) {
            $this->createLanguage($manager, $language, $definition);
        }

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param string $code
     * @param array $options
     *
     * @return Language
     */
    protected function createLanguage(ObjectManager $manager, $code, array $options)
    {
        $criteria = [];
        if (!empty($options['user'])) {
            $criteria = ['username' => $options['user']];
        }

        /* @var $user User */
        $user = $manager->getRepository(User::class)->findOneBy($criteria);

        $language = new Language();
        $language
            ->setCode($code)
            ->setEnabled(!empty($options['enabled']))
            ->setOwner($user)
            ->setOrganization($user->getOrganization());

        $manager->persist($language);
        $this->addReference($code, $language);

        return $language;
    }
}
