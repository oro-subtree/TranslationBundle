<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Test\FormIntegrationTestCase;

use Oro\Bundle\TranslationBundle\Entity\Repository\LanguageRepository;
use Oro\Bundle\TranslationBundle\Form\Type\AddLanguageType;

use Oro\Bundle\LocaleBundle\Form\Type\LanguageType;
use Symfony\Component\Intl\Util\IntlTestHelper;

class AddLanguageTypeTest extends FormIntegrationTestCase
{
    /** @var LanguageType */
    protected $formType;

    /** @var \PHPUnit_Framework_MockObject_MockObject|LanguageRepository */
    protected $repository;

    protected function setUp()
    {
        parent::setUp();

        $this->repository = $this->getMockBuilder(LanguageRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->formType = new AddLanguageType($this->repository);
    }

    protected function tearDown()
    {
        parent::tearDown();
        unset($this->repository, $this->formType);
    }

    public function testGetName()
    {
        $this->assertEquals('oro_translation_add_language', $this->formType->getName());
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals('oro_translation_add_language', $this->formType->getBlockPrefix());
    }

    public function testGetParent()
    {
        $this->assertEquals('locale', $this->formType->getParent());
    }

    /**
     * @dataProvider buildFormProvider
     *
     * @param array $currentCodes
     * @param array $codesExpected
     */
    public function testBuildForm(array $currentCodes, array $codesExpected)
    {
        IntlTestHelper::requireIntl($this);

        $this->repository->expects($this->any())
            ->method('getAvailableLanguageCodes')
            ->willReturn($currentCodes);

        $form = $this->factory->create($this->formType);
        $choices = $form->getConfig()->getOption('choices');

        $this->assertEquals($codesExpected, array_intersect($codesExpected, $choices));
        $this->assertEmpty(array_intersect($currentCodes, $choices));
    }

    /**
     * @return array
     */
    public function buildFormProvider()
    {
        return [
            'no current languages' => [
                [],
                ['en','fr', 'pl'],
            ],
            '1 current language' => [
                ['en'],
                ['fr', 'ru', 'pl'],
            ],
            '2 current languages' => [
                ['en', 'fr'],
                ['pl'],
            ],
        ];
    }
}
