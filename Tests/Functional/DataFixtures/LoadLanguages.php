<?php

namespace Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\UserBundle\Entity\User;

class LoadLanguages extends AbstractFixture
{
    const LANGUAGE1 = 'en_CA';
    const LANGUAGE2 = 'fr_FR';

    const LANGUAGE1_NAME = 'English (Canada)';
    const LANGUAGE2_NAME = 'French (France)';

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->createLanguage($manager, self::LANGUAGE1, false);
        $this->createLanguage($manager, self::LANGUAGE2, true);

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param string $code
     * @param bool $isEnabled
     *
     * @return Language
     */
    protected function createLanguage(ObjectManager $manager, $code, $isEnabled)
    {
        $language = new Language();
        $user = $manager->getRepository(User::class)->findOneBy([]);
        $organization = $manager->getRepository(Organization::class)->findOneBy([]);

        $language
            ->setCode($code)
            ->setEnabled($isEnabled)
            ->setOwner($user)
            ->setOrganization($organization);
        $manager->persist($language);
        $this->addReference($code, $language);

        return $language;
    }
}
