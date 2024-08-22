<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Parameters;

use CultuurNet\UDB3\Search\DateTimeFactory;
use CultuurNet\UDB3\Search\Label\LabelName;
use CultuurNet\UDB3\Search\Offer\WorkflowStatus;
use DateTime;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ArrayParameterBagAdapterTest extends TestCase
{
    /**
     * @test
     * @dataProvider arrayParameterDataProvider
     */
    public function it_should_parse_a_parameter_as_an_array(
        array $parameters,
        string $parameterName,
        array $expectedScalarValues
    ): void {
        $parameterBag = new ArrayParameterBagAdapter($parameters);
        $actualScalarValues = $parameterBag->getArrayFromParameter($parameterName);
        $this->assertEquals($expectedScalarValues, $actualScalarValues);
    }

    /**
     * @test
     * @dataProvider arrayParameterDataProvider
     */
    public function it_should_apply_an_optional_callback_to_each_value_of_a_parameter(
        array $parameters,
        string $parameterName,
        array $expectedScalarValues,
        callable $callback,
        array $expectedCastedValues
    ): void {
        $parameterBag = new ArrayParameterBagAdapter($parameters);
        $actualCastedValues = $parameterBag->getArrayFromParameter($parameterName, $callback);
        $this->assertEquals($expectedCastedValues, $actualCastedValues);
    }

    public function arrayParameterDataProvider(): array
    {
        $callback = static fn ($label): LabelName => new LabelName($label);

        return [
            [
                'parameters' => ['labels' => ['UiTPASLeuven', 'Paspartoe']],
                'parameter' => 'labels',
                'values' => ['UiTPASLeuven', 'Paspartoe'],
                'callback' => $callback,
                'casted' => [new LabelName('UiTPASLeuven'), new LabelName('Paspartoe')],
            ],
            [
                'parameters' => ['labels' => ['UiTPASLeuven']],
                'parameter' => 'labels',
                'values' => ['UiTPASLeuven'],
                'callback' => $callback,
                'casted' => [new LabelName('UiTPASLeuven')],
            ],
            [
                'parameters' => ['labels' => 'UiTPASLeuven'],
                'parameter' => 'labels',
                'values' => ['UiTPASLeuven'],
                'callback' => $callback,
                'casted' => [new LabelName('UiTPASLeuven')],
            ],
            [
                'parameters' => ['labels' => []],
                'parameter' => 'labels',
                'values' => [],
                'callback' => $callback,
                'casted' => [],
            ],
            [
                'parameters' => ['labels' => null],
                'parameter' => 'labels',
                'values' => [],
                'callback' => $callback,
                'casted' => [],
            ],
            [
                'parameters' => [],
                'parameter' => 'labels',
                'values' => [],
                'callback' => $callback,
                'casted' => [],
            ],
        ];
    }

    /**
     * @test
     */
    public function it_should_parse_a_parameter_as_a_single_string(): void
    {
        $parameterBag = new ArrayParameterBagAdapter(['workflowStatus' => 'DRAFT']);
        $expected = 'DRAFT';
        $actual = $parameterBag->getStringFromParameter('workflowStatus');
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_does_throw_argument_exception_when_passing_array_for_string_parameter(): void
    {
        $parameterBag = new ArrayParameterBagAdapter(['workflowStatus' => ['DRAFT', 'READY_FOR_VALIDATION']]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The parameter "workflowStatus" can only have a single value.');

        $parameterBag->getStringFromParameter('workflowStatus');
    }

    /**
     * @test
     */
    public function it_should_apply_a_callback_to_the_single_string_value(): void
    {
        $parameterBag = new ArrayParameterBagAdapter(['workflowStatus' => 'DRAFT']);

        $callback = static fn ($workflowStatus): WorkflowStatus => new WorkflowStatus($workflowStatus);

        $expected = new WorkflowStatus('DRAFT');
        $actual = $parameterBag->getStringFromParameter('workflowStatus', null, $callback);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     * @codingStandardsIgnoreStart
     */
    public function it_should_return_a_default_for_a_single_string_parameter_if_it_is_empty_and_a_default_is_available_and_defaults_are_enabled(): void
    {
        // @codingStandardsIgnoreEnd

        $parameterBag = new ArrayParameterBagAdapter([]);
        $default = 'APPROVED';

        $expected = 'APPROVED';
        $actual = $parameterBag->getStringFromParameter('workflowStatus', $default);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_apply_a_callback_to_the_single_string_default_value(): void
    {
        $parameterBag = new ArrayParameterBagAdapter([]);
        $default = 'APPROVED';

        $callback = static fn ($workflowStatus): WorkflowStatus => new WorkflowStatus($workflowStatus);

        $expected = new WorkflowStatus('APPROVED');
        $actual = $parameterBag->getStringFromParameter('workflowStatus', $default, $callback);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_return_null_for_a_single_string_parameter_if_the_parameter_value_is_a_wildcard(): void
    {
        $parameterBag = new ArrayParameterBagAdapter(['workflowStatus' => '*']);
        $actual = $parameterBag->getStringFromParameter('workflowStatus');
        $this->assertNull($actual);
    }

    /**
     * @test
     * @codingStandardsIgnoreStart
     */
    public function it_should_return_null_for_a_single_string_parameter_if_it_is_is_empty_and_no_default_is_available(): void
    {
        // @codingStandardsIgnoreEnd
        $parameterBag = new ArrayParameterBagAdapter([]);
        $actual = $parameterBag->getStringFromParameter('workflowStatus');
        $this->assertNull($actual);
    }

    /**
     * @test
     * @codingStandardsIgnoreStart
     */
    public function it_should_return_null_for_a_single_string_parameter_if_it_is_is_empty_and_defaults_are_disabled(): void
    {
        // @codingStandardsIgnoreEnd
        $parameterBag = new ArrayParameterBagAdapter(['disableDefaultFilters' => true]);
        $default = 'APPROVED';

        $actual = $parameterBag->getStringFromParameter('workflowStatus', $default);

        $this->assertNull($actual);
    }

    /**
     * @test
     */
    public function it_should_parse_a_parameter_as_a_delimited_string_and_return_an_array(): void
    {
        $parameterBag = new ArrayParameterBagAdapter(['workflowStatus' => 'READY_FOR_VALIDATION,APPROVED']);

        $expected = ['READY_FOR_VALIDATION', 'APPROVED'];
        $actual = $parameterBag->getExplodedStringFromParameter('workflowStatus');
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_apply_a_callback_to_each_value_of_the_delimited_string_array(): void
    {
        $parameterBag = new ArrayParameterBagAdapter(['workflowStatus' => 'READY_FOR_VALIDATION,APPROVED']);

        $callback = static fn ($workflowStatus): WorkflowStatus => new WorkflowStatus($workflowStatus);

        $expected = [new WorkflowStatus('READY_FOR_VALIDATION'), new WorkflowStatus('APPROVED')];
        $actual = $parameterBag->getExplodedStringFromParameter('workflowStatus', null, $callback);

        $this->assertArrayContentsAreEqual($expected, $actual);
    }

    /**
     * @test
     * @codingStandardsIgnoreStart
     */
    public function it_should_return_a_default_as_an_array_for_a_delimited_string_parameter_if_it_is_empty_and_a_default_is_available_and_defaults_are_enabled(): void
    {
        // @codingStandardsIgnoreEnd

        $parameterBag = new ArrayParameterBagAdapter([]);
        $default = 'READY_FOR_VALIDATION,APPROVED';

        $expected = ['READY_FOR_VALIDATION', 'APPROVED'];
        $actual = $parameterBag->getExplodedStringFromParameter('workflowStatus', $default);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_apply_a_callback_to_each_value_of_the_delimited_string_default_array(): void
    {
        $parameterBag = new ArrayParameterBagAdapter([]);
        $default = 'READY_FOR_VALIDATION,APPROVED';

        $callback = static fn ($workflowStatus): WorkflowStatus => new WorkflowStatus($workflowStatus);

        $expected = [new WorkflowStatus('READY_FOR_VALIDATION'), new WorkflowStatus('APPROVED')];
        $actual = $parameterBag->getExplodedStringFromParameter('workflowStatus', $default, $callback);

        $this->assertArrayContentsAreEqual($expected, $actual);
    }

    /**
     * @test
     * @codingStandardsIgnoreStart
     */
    public function it_should_return_an_empty_array_for_a_delimited_string_parameter_if_the_string_value_is_a_wildcard(): void
    {
        // @codingStandardsIgnoreEnd
        $parameterBag = new ArrayParameterBagAdapter(['workflowStatus' => '*']);
        $expected = [];
        $actual = $parameterBag->getExplodedStringFromParameter('workflowStatus');
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     * @codingStandardsIgnoreStart
     */
    public function it_should_return_an_empty_array_for_a_delimited_string_parameter_if_it_is_is_empty_and_no_default_is_available(): void
    {
        // @codingStandardsIgnoreEnd

        $parameterBag = new ArrayParameterBagAdapter([]);
        $expected = [];
        $actual = $parameterBag->getExplodedStringFromParameter('workflowStatus');
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     * @codingStandardsIgnoreStart
     */
    public function it_should_return_an_empty_array_for_a_delimited_string_parameter_if_it_is_is_empty_and_defaults_are_disabled(): void
    {
        // @codingStandardsIgnoreEnd

        $parameterBag = new ArrayParameterBagAdapter(['disableDefaultFilters' => true]);
        $default = 'READY_FOR_VALIDATION,APPROVED';

        $expected = [];
        $actual = $parameterBag->getExplodedStringFromParameter('workflowStatus', $default);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     * @param bool|string|int $parameterValue
     * @dataProvider booleanDataProvider
     */
    public function it_should_parse_a_boolean_value_from_a_parameter(
        $parameterValue,
        ?bool $expectedValue
    ): void {
        $parameterBag = new ArrayParameterBagAdapter(['uitpas' => $parameterValue]);
        $actualValue = $parameterBag->getBooleanFromParameter('uitpas');
        $this->assertSame($expectedValue, $actualValue);
    }

    public function booleanDataProvider(): array
    {
        return [
            [
                false,
                false,
            ],
            [
                true,
                true,
            ],
            [
                'false',
                false,
            ],
            [
                'FALSE',
                false,
            ],
            [
                '0',
                false,
            ],
            [
                0,
                false,
            ],
            [
                'true',
                true,
            ],
            [
                'TRUE',
                true,
            ],
            [
                '1',
                true,
            ],
            [
                1,
                true,
            ],
            [
                '',
                null,
            ],
            [
                null,
                null,
            ],
        ];
    }

    /**
     * @test
     * @codingStandardsIgnoreStart
     */
    public function it_should_return_a_default_for_a_boolean_parameter_if_it_is_empty_and_a_default_is_available_and_defaults_are_enabled(): void
    {
        // @codingStandardsIgnoreEnd

        $parameterBag = new ArrayParameterBagAdapter([]);
        $default = 'true';

        $expected = true;
        $actual = $parameterBag->getBooleanFromParameter('uitpas', $default);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_return_null_for_a_boolean_parameter_if_the_parameter_value_is_a_wildcard(): void
    {
        $parameterBag = new ArrayParameterBagAdapter(['uitpas' => '*']);
        $actual = $parameterBag->getBooleanFromParameter('uitpas');
        $this->assertNull($actual);
    }

    /**
     * @test
     */
    public function it_should_return_null_for_a_boolean_parameter_if_it_is_is_empty_and_no_default_is_available(): void
    {
        $parameterBag = new ArrayParameterBagAdapter([]);
        $actual = $parameterBag->getStringFromParameter('uitpas');
        $this->assertNull($actual);
    }

    /**
     * @test
     */
    public function it_should_return_null_for_a_boolean_parameter_if_it_is_is_empty_and_defaults_are_disabled(): void
    {
        $parameterBag = new ArrayParameterBagAdapter(['disableDefaultFilters' => true]);
        $default = true;

        $actual = $parameterBag->getBooleanFromParameter('uitpas', $default);

        $this->assertNull($actual);
    }

    /**
     * @test
     */
    public function it_should_parse_a_datetime_from_a_parameter(): void
    {
        $parameterBag = new ArrayParameterBagAdapter(['availableFrom' => '2017-04-26T12:20:05+01:00']);

        $expected = DateTimeFactory::fromAtom('2017-04-26T12:20:05+01:00');
        $actual = $parameterBag->getDateTimeFromParameter('availableFrom');

        $this->assertDateTimeEquals($expected, $actual);
    }

    /**
     * @test
     * @codingStandardsIgnoreStart
     */
    public function it_should_return_a_default_for_a_datetime_parameter_if_it_is_empty_and_a_default_is_available_and_defaults_are_enabled(): void
    {
        // @codingStandardsIgnoreEnd

        $parameterBag = new ArrayParameterBagAdapter([]);
        $default = '2017-04-26T12:20:05+01:00';

        $expected = DateTimeFactory::fromAtom('2017-04-26T12:20:05+01:00');
        $actual = $parameterBag->getDateTimeFromParameter('availableFrom', $default);

        $this->assertDateTimeEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_return_null_for_a_datetime_parameter_if_the_parameter_value_is_a_wildcard(): void
    {
        $parameterBag = new ArrayParameterBagAdapter(['availableFrom' => '*']);
        $actual = $parameterBag->getDateTimeFromParameter('availableFrom');
        $this->assertNull($actual);
    }

    /**
     * @test
     */
    public function it_should_return_null_for_a_datetime_parameter_if_it_is_is_empty_and_no_default_is_available(): void
    {
        $parameterBag = new ArrayParameterBagAdapter([]);
        $actual = $parameterBag->getDateTimeFromParameter('availableFrom');
        $this->assertNull($actual);
    }

    /**
     * @test
     */
    public function it_should_return_null_for_a_datetime_parameter_if_it_is_is_empty_and_defaults_are_disabled(): void
    {
        $parameterBag = new ArrayParameterBagAdapter(['disableDefaultFilters' => true]);
        $default = '2017-04-26T12:20:05+01:00';

        $actual = $parameterBag->getDateTimeFromParameter('availableFrom', $default);

        $this->assertNull($actual);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_a_datetime_parameter_can_not_be_parsed(): void
    {
        $parameterBag = new ArrayParameterBagAdapter(['availableFrom' => '26/04/2017']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'availableFrom should be an ISO-8601 datetime, for example 2017-04-26T12:20:05+01:00'
        );

        $parameterBag->getDateTimeFromParameter('availableFrom');
    }


    private function assertArrayContentsAreEqual(array $expected, array $actual): void
    {
        $this->assertCount(count($expected), $actual);

        foreach ($expected as $key => $expectedValue) {
            $this->assertEquals($expectedValue, $actual[$key]);
        }
    }


    private function assertDateTimeEquals(DateTimeImmutable $expected, DateTimeImmutable $actual): void
    {
        $this->assertEquals(
            $expected->format(DateTime::ATOM),
            $actual->format(DateTime::ATOM)
        );
    }
}
