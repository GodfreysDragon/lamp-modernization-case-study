<?php

namespace Database\Seeders;

use App\Enums\TicketStatus;
use App\Models\Comment;
use App\Models\Customer;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Demo data for local development. Production data comes from `php artisan legacy:import`.
     */
    public function run(): void
    {
        $admin = User::factory()->admin()->create([
            'username' => 'admin',
            'name' => 'Dave Patterson',
            'email' => 'dave@acme-support.test',
            'password' => 'admin123',
        ]);

        $agents = User::factory(3)->create()->push($admin);
        $customers = Customer::factory(15)->create();

        Ticket::factory(60)
            ->recycle($customers)
            ->state(fn () => [
                'assignee_id' => fake()->boolean(80) ? $agents->random()->id : null,
                'status' => fake()->randomElement(TicketStatus::cases()),
                'created_at' => fake()->dateTimeBetween('-10 days'),
            ])
            ->create()
            ->each(fn (Ticket $ticket) => Comment::factory(fake()->numberBetween(0, 4))
                ->recycle($agents)
                ->create(['ticket_id' => $ticket->id]));
    }
}
