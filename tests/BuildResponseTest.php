<?php

namespace MarcinOrlowski\ResponseBuilder\Tests;

/**
 * Laravel API Response Builder
 *
 * @package   MarcinOrlowski\ResponseBuilder
 *
 * @author    Marcin Orlowski <mail (#) marcinorlowski (.) com>
 * @copyright 2016-2019 Marcin Orlowski
 * @license   http://www.opensource.org/licenses/mit-license.php MIT
 * @link      https://github.com/MarcinOrlowski/laravel-api-response-builder
 */

use Illuminate\Support\Facades\Config;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;

class BuildResponseTest extends TestCase
{
	/**
	 * Tests if buildResponse() would properly handle auto conversion
	 */
	public function testBuildResponseClassAutoConversionSingleElement(): void
	{
		// GIVEN model object with randomly set member value
		$model_val = $this->getRandomString('model');
		$model = new TestModel($model_val);

		// AND having its class configured for auto conversion
		$model_class_name = get_class($model);
		$classes = [
			$model_class_name => [
				ResponseBuilder::KEY_KEY    => $this->getRandomString('single_item_key'),
				ResponseBuilder::KEY_METHOD => 'toArray',
			],
		];
		Config::set(ResponseBuilder::CONF_KEY_CLASSES, $classes);

		// WHEN this object is returned
		$this->response = ResponseBuilder::success($model);
		$j = $this->getResponseSuccessObject();

		// THEN returned response object should have it auto converted
		$this->assertNotNull($j->data);
		$this->assertObjectHasAttribute($classes[ $model_class_name ]['key'], $j->data, 'No single item key element not found');
		$this->assertEquals($model_val, $j->data->{$classes[ $model_class_name ]['key']}->val);
	}

	/**
	 * Tests if buildResponse() would properly handle auto conversion when mapped class is part of bigger data set
	 */
	public function testBuildResponseClassAutoConversionAsPartOfDataset(): void
	{
		// GIVEN model object with randomly set member value
		$model_1_val = $this->getRandomString('model_1');
		$model_1 = new TestModel($model_1_val);

		$model_2_val = $this->getRandomString('model_2');
		$model_2 = new TestModel($model_2_val);

		$model_1_data_key = 'model-data-key_1';
		$model_2_data_key = 'model-data-key_2';

		// AND having its class configured for auto conversion
		$model_class_name = get_class($model_1);
		$classes = [
			$model_class_name => [
				ResponseBuilder::KEY_KEY    => 'should-not-be-used',
				ResponseBuilder::KEY_METHOD => 'toArray',
			],
		];
		Config::set(ResponseBuilder::CONF_KEY_CLASSES, $classes);

		// AND having the object as part of bigger data set
		$tmp_base = [];
		for ($i = 0; $i < 1; $i++) {
			$tmp_base[ $this->getRandomString("key{$i}") ] = $this->getRandomString("val{$i}");
		}

		$data = $tmp_base;
		$data[ $model_1_data_key ] = $model_1;
		$data['nested'][ $model_2_data_key ] = $model_2;

		// WHEN this object is returned
		$this->response = ResponseBuilder::success($data);
		$j = $this->getResponseSuccessObject();

		// THEN returned response object should have it auto converted properly
		$this->assertNotNull($j->data);

		// single key item must not be used
		$this->assertObjectNotHasAttribute($classes[ $model_class_name ]['key'], $j->data, 'Single item key found but should not');
		// instead original key must be preserved
		$this->assertObjectHasAttribute($model_1_data_key, $j->data, "Unable to find '{$model_1_data_key}' model 1 key'");
		$this->assertEquals($model_1_val, $j->data->{$model_1_data_key}->val);

		$this->assertObjectHasAttribute('nested', $j->data);
		$this->assertObjectHasAttribute($model_2_data_key, $j->data->nested, "Unable to find '{$model_2_data_key}' model 2 key'");
		$this->assertEquals($model_2_val, $j->data->nested->{$model_2_data_key}->val);

		// and all other elements of data set should also be here
		foreach ($tmp_base as $key => $val) {
			$this->assertObjectHasAttribute($key, $j->data);
			$this->assertEquals($val, $j->data->{$key});
		}
	}

	/**
	 * Checks if buildResponse() would throw InvalidArgument exception on unusupported payload type
	 *
	 * @param mixed $data Test data as yelded by dataProvider
	 *
	 * @dataProvider dataProviderTestBuildResponseInvalidDataType
	 */
	public function testBuildResponseInvalidDataType($data): void
	{
		$this->expectException(\InvalidArgumentException::class);
		ResponseBuilder::success($data);
	}

	/**
	 * Data provider for testBuildResponse_InvalidDataType test
	 */
	public function dataProviderTestBuildResponseInvalidDataType(): array
	{
		return [
			[(object)['no' => 'mapping']],
			['invalid'],
			[666],
		];
	}
}
