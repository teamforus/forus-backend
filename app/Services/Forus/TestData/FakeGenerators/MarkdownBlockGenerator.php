<?php

namespace App\Services\Forus\TestData\FakeGenerators;

use Faker\Generator;

class MarkdownBlockGenerator
{
    protected Generator $faker;

    /**
     * Constructor.
     *
     * @param Generator $faker A configured Faker instance
     */
    public function __construct(Generator $faker)
    {
        $this->faker = $faker;
    }

    /**
     * Generate a contact details block in markdown format.
     *
     * @return string
     */
    public function generateContactDetails(): string
    {
        $email = $this->faker->unique()->safeEmail();
        $phone = $this->faker->phoneNumber();
        $streetAddress = $this->faker->streetAddress();
        $postcode = $this->faker->postcode();
        $city = $this->faker->city();

        return <<<MARKDOWN
            ### Contactgegevens

            **E-mailadres:** [$email](mailto:$email)  
            **Telefoonnummer:** $phone  
            **Adres:** $streetAddress, $postcode $city
            MARKDOWN;
    }

    /**
     * Generate an opening times block in markdown format.
     *
     * @return string
     */
    public function generateOpeningTimes(): string
    {
        $weekStart = $this->faker->time('H:i', '09:00');
        $weekEnd = $this->faker->time('H:i', '17:00');
        $friStart = $this->faker->time('H:i', '08:30');
        $friEnd = $this->faker->time('H:i', '13:00');

        return <<<MARKDOWN
            ### Openingstijden

            **Maandag t/m donderdag:** $weekStart - $weekEnd  
            **Vrijdag:** $friStart - $friEnd
            MARKDOWN;
    }
}
