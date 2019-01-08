<?php declare(strict_types=1);

namespace Helper;

/**
 * Create fake entities for external services.
 */
class Faker extends \Codeception\Module
{
    /**
     * @var \Faker\Generator
     */
    protected $faker;

    /**
     * Called before executing a suite.
     *
     * @param array $settings
     */
    public function _beforeSuite($settings = []): void
    {
        $this->faker = \Faker\Factory::create();
        $this->faker->seed(42);
    }


    /**
     * Create a fake IAM user (without organization).
     *
     * @param array $info
     * @return \stdClass
     */
    public function fakeUser(array $info = []): \stdClass
    {
        $faked = [
            'id' => $this->faker->uuid,
            'name' => $this->faker->name,
            'email' => $this->faker->safeEmail,
            'authz_groups' => [],
        ];

        return (object)($info + $faked);
    }

    /**
     * Create a fake IAM organization.
     *
     * @param array $info
     * @return \stdClass
     */
    public function fakeOrganization(array $info = []): \stdClass
    {
        $faked = [
            'id' => $this->faker->uuid,
            'name' => $this->faker->company,
            'type' => 'client',
        ];

        return (object)($info + $faked);
    }

    /**
     * Create a fake IAM employee (user with organization).
     *
     * @param array $info
     * @return \stdClass
     */
    public function fakeEmployee(array $info = []): \stdClass
    {
        $user = $this->fakeUser(array_without($info, ['organization']));
        $organization = $this->fakeOrganization($info['organization'] ?? []);

        return (object)((array)$user + ['organization' => $organization]);
    }


    /**
     * Create a fake DMS document.
     *
     * @param array  $info
     * @return \stdClass
     */
    public function fakeDocument(array $info = []): \stdClass
    {
        $id = $this->faker->uuid;

        $faked = [
            'id' => $id,
            'name' => $this->faker->sentence(mt_rand(1, 3)),
            'url' => sprintf('/service/dms/documents/%s.pdf', $id)
        ];

        return (object)($info + $faked);
    }


    /**
     * License information.
     *
     * @param array $info
     * @return \stdClass
     */
    public function fakeLicense(array $info = []): \stdClass
    {
        $faked = [
            'id' => $this->faker->uuid,
            'name' => $this->faker->sentence(mt_rand(1, 3)),
        ];

        return (object)($info + $faked);
    }
}
