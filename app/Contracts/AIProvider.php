<?php

namespace App\Contracts;

use App\Integrations\Support\ChatAnswer;

/**
 * Provider-independent assistant.
 *
 * Implementations receive pre-retrieved, platform-controlled context and MUST NOT
 * invent exam centres, centre assignments, live fares, timetables, transport
 * availability, hotel vacancies or official policies. When evidence is missing the
 * honest answer is "unavailable", not a guess.
 */
interface AIProvider
{
    /** Stable identifier, e.g. "faq" or "openai". */
    public function name(): string;

    /** False when required configuration/credentials are missing. */
    public function isAvailable(): bool;

    /**
     * @param  array{
     *     centres?: array,
     *     services?: array,
     *     faqs?: array,
     *     examinations?: array,
     *     itinerary?: array|null,
     * }  $context  Grounded facts retrieved by the application (never by the model).
     */
    public function answer(string $question, array $context = []): ChatAnswer;
}
