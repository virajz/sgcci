  <?php

    use App\Models\Booking;
    use Illuminate\Database\Migrations\Migration;

    return new class extends Migration
    {
        public function up(): void
        {
            // For bookings that already have invited guests, cap the limit to exactly
            // how many they've invited (so existing guests remain valid, but no new ones can be added).
            // Everyone else gets limit 0.
            Booking::withCount('invitedGuests')->each(function (Booking $booking) {
                $booking->updateQuietly([
                    'invited_guests_limit' => $booking->invited_guests_count,
                ]);
            });
        }

        public function down(): void
        {
            //
        }
    };
