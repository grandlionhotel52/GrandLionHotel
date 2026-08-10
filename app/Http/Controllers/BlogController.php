<?php

namespace App\Http\Controllers;

class BlogController extends Controller
{
    public function index()
    {
        $posts = collect($this->posts());
        $featured = $posts->first();
        $latest = $posts->slice(1)->values();
        $topics = $posts->pluck('category')->unique()->values();

        return view('blog.index', compact('featured', 'latest', 'topics'));
    }

    public function show(string $slug)
    {
        $posts = collect($this->posts());
        $post = $posts->firstWhere('slug', $slug);

        abort_unless($post, 404);

        $related = $posts
            ->where('slug', '!=', $slug)
            ->where('category', $post['category'])
            ->take(2)
            ->values();

        if ($related->count() < 2) {
            $related = $related->concat(
                $posts
                    ->where('slug', '!=', $slug)
                    ->whereNotIn('slug', $related->pluck('slug'))
                    ->take(2 - $related->count())
            )->values();
        }

        return view('blog.show', compact('post', 'related'));
    }

    private function posts(): array
    {
        return [
            [
                'slug' => 'best-time-to-book-a-city-hotel',
                'title' => 'Best Time To Book A City Hotel In 2026',
                'excerpt' => 'Smart booking windows and date strategies to get better rates without sacrificing quality.',
                'image' => 'https://images.unsplash.com/photo-1455587734955-081b22074882?auto=format&fit=crop&w=1400&q=80',
                'date' => 'March 1, 2026',
                'category' => 'Booking Strategy',
                'read_time' => '5 min read',
                'intro' => 'Booking at the right moment often saves more than chasing random promos. A clear timing strategy helps you secure better rates without giving up comfort.',
                'highlights' => [
                    'Book city stays 30 to 45 days before arrival for the best price and room balance.',
                    'Reserve earlier for holidays, concerts, and long weekends when inventory shrinks fast.',
                    'Compare refundable and non-refundable rates based on how fixed your travel dates are.',
                ],
                'sections' => [
                    [
                        'heading' => 'Understand the demand window',
                        'body' => 'City hotels usually price rooms around expected occupancy. Once events fill nearby venues, rates can rise quickly. Checking your destination calendar first gives you an edge before demand spikes.',
                    ],
                    [
                        'heading' => 'Use weekday check-ins to lower total cost',
                        'body' => 'For many business-oriented cities, Tuesday and Wednesday check-ins are often less expensive than Friday arrivals. Even shifting one night can reduce the total reservation amount.',
                    ],
                    [
                        'heading' => 'Watch total value, not headline price',
                        'body' => 'A slightly higher nightly rate may include airport transfer, late checkout, or flexible cancellation. Compare final value and policies before deciding based only on the base price.',
                    ],
                ],
                'conclusion' => 'The best booking decision is rarely about finding the absolute lowest number. Give yourself enough lead time, compare the complete stay value, and choose terms that match how certain your travel plans really are. That combination protects both your budget and your peace of mind.',
                'tags' => ['Rate Strategy', 'Trip Planning', 'City Stay'],
                'author' => [
                    'name' => 'Lara Mendoza',
                    'role' => 'Hospitality Editor',
                ],
            ],
            [
                'slug' => 'how-to-pick-the-right-room-type',
                'title' => 'How To Pick The Right Room Type For Your Trip',
                'excerpt' => 'From standard to suite, choose based on stay duration, purpose, and comfort needs.',
                'image' => 'https://images.unsplash.com/photo-1611892440504-42a792e24d32?auto=format&fit=crop&w=1400&q=80',
                'date' => 'February 20, 2026',
                'category' => 'Room Selection',
                'read_time' => '4 min read',
                'intro' => 'The right room depends on your trip goals, not just your budget. Matching layout and amenities to your schedule improves both comfort and productivity.',
                'highlights' => [
                    'For solo or short business trips, prioritize desk space, stable Wi-Fi, and quick access.',
                    'For families and longer stays, choose layouts with lounge space and larger storage.',
                    'Always confirm bed configuration, cancellation policy, and included amenities.',
                ],
                'sections' => [
                    [
                        'heading' => 'Choose based on trip purpose',
                        'body' => 'If you are mostly out for meetings or tours, you can often save with a well-designed standard room. If you plan to rest, work, or dine in-room, extra space pays off.',
                    ],
                    [
                        'heading' => 'Account for stay length',
                        'body' => 'Two nights and seven nights feel very different. On longer bookings, features like wardrobe size, seating area, and natural light make daily routines more comfortable.',
                    ],
                    [
                        'heading' => 'Review details before confirming',
                        'body' => 'Look closely at inclusions like parking, airport transfer, and early check-in access. Small policy details can matter more than room photos when you are finalizing your booking.',
                    ],
                ],
                'conclusion' => 'Start with the purpose and length of your trip, then choose the smallest room that comfortably supports those needs. Confirm the bed, space, view, and inclusions before paying. A thoughtful match will usually improve the stay more than upgrading simply because a room carries a premium label.',
                'tags' => ['Room Types', 'Comfort', 'Family Travel'],
                'author' => [
                    'name' => 'Miguel Santos',
                    'role' => 'Stay Experience Writer',
                ],
            ],
            [
                'slug' => 'hotel-safety-and-travel-checklist',
                'title' => 'Hotel Safety And Travel Checklist',
                'excerpt' => 'A practical pre-check-in checklist for safer and smoother hotel stays.',
                'image' => 'https://images.unsplash.com/photo-1576675784201-0e142b423952?auto=format&fit=crop&w=1400&q=80',
                'date' => 'January 28, 2026',
                'category' => 'Travel Safety',
                'read_time' => '6 min read',
                'intro' => 'A safer trip starts before arrival. A few preparation steps can prevent check-in delays, payment confusion, and avoidable security risks.',
                'highlights' => [
                    'Save your booking confirmation, payment proof, and hotel contact in one place.',
                    'Keep IDs ready and verify check-in/check-out windows before arrival.',
                    'During your stay, use in-room safes and review billing before departure.',
                ],
                'sections' => [
                    [
                        'heading' => 'Prepare documents in advance',
                        'body' => 'Store digital and printed copies of your reservation details. Having your confirmation number and receipt ready helps front desk teams resolve issues quickly.',
                    ],
                    [
                        'heading' => 'Secure essentials in-room',
                        'body' => 'Use the room safe for passports, cards, and electronics when you are away. Keep your door locked and avoid sharing room numbers publicly in common areas.',
                    ],
                    [
                        'heading' => 'Do a quick checkout audit',
                        'body' => 'Review mini-bar, room service, and incidental charges before leaving. A two-minute billing check prevents follow-up disputes after your trip.',
                    ],
                ],
                'conclusion' => 'Good travel safety is mostly a collection of small, repeatable habits. Organize your documents, protect valuables, verify official payment channels, and review charges before leaving. These simple checks reduce uncertainty and let you focus on enjoying the trip itself.',
                'tags' => ['Checklist', 'Safety Tips', 'Guest Guide'],
                'author' => [
                    'name' => 'Andrea Cruz',
                    'role' => 'Guest Safety Contributor',
                ],
            ],
            [
                'slug' => 'weekend-stay-itinerary-near-the-city-center',
                'title' => 'Weekend Stay Itinerary Near The City Center',
                'excerpt' => 'A balanced two-day plan for guests who want culture, exploration, and rest without rushing.',
                'image' => 'https://images.unsplash.com/photo-1445019980597-93fa8acb246c?auto=format&fit=crop&w=1400&q=80',
                'date' => 'January 10, 2026',
                'category' => 'Local Guide',
                'read_time' => '5 min read',
                'intro' => 'City weekends are better with structure. A simple itinerary helps you enjoy top spots while keeping enough downtime between activities.',
                'highlights' => [
                    'Plan mornings for light sightseeing and evenings for relaxed city experiences.',
                    'Book one anchor activity per day to avoid overpacked schedules.',
                    'Keep one open block for spontaneous plans or rest.',
                ],
                'sections' => [
                    [
                        'heading' => 'Day 1: Arrival and nearby exploration',
                        'body' => 'After check-in, focus on attractions within walking distance so you can settle in. A relaxed first evening keeps your energy high for the second day.',
                    ],
                    [
                        'heading' => 'Day 2: Signature experience',
                        'body' => 'Reserve one premium activity in advance, such as a museum pass or rooftop dinner. Build the rest of the day around flexible plans and easy transit routes.',
                    ],
                    [
                        'heading' => 'Departure: Keep the final morning light',
                        'body' => 'Schedule checkout with enough buffer for transport and last-minute purchases. A low-pressure departure closes your weekend stay on a better note.',
                    ],
                ],
                'conclusion' => 'A rewarding weekend does not need a packed schedule. Choose a few meaningful experiences, group plans by location, and protect time for meals and rest. Leaving some space in the itinerary makes the trip feel personal instead of rushed.',
                'tags' => ['Weekend Plan', 'City Guide', 'Leisure'],
                'author' => [
                    'name' => 'Paolo Reyes',
                    'role' => 'City Travel Editor',
                ],
            ],
            [
                'slug' => 'business-travel-check-in-routine',
                'title' => 'Business Travel Check-In Routine That Saves Time',
                'excerpt' => 'A repeatable routine for faster arrivals, organized schedules, and better workday flow.',
                'image' => 'https://images.unsplash.com/photo-1468824357306-a439d58ccb1c?auto=format&fit=crop&w=1400&q=80',
                'date' => 'December 22, 2025',
                'category' => 'Business Travel',
                'read_time' => '4 min read',
                'intro' => 'Frequent business trips become easier with a reliable check-in routine. Small habits reduce friction and free time for meetings and recovery.',
                'highlights' => [
                    'Use one travel folder for IDs, invoices, and schedule details.',
                    'Request key room features in advance: desk, quiet floor, and fast Wi-Fi.',
                    'Set a 10-minute setup routine once you reach your room.',
                ],
                'sections' => [
                    [
                        'heading' => 'Before arrival: simplify logistics',
                        'body' => 'Confirm transport, check-in time, and invoice details before landing. This removes back-and-forth at the front desk during busy arrival periods.',
                    ],
                    [
                        'heading' => 'At check-in: prioritize essentials',
                        'body' => 'Ask about Wi-Fi access, workspace hours, and quiet-room availability immediately. Getting these details early helps you structure your next business day.',
                    ],
                    [
                        'heading' => 'In-room setup: ready in 10 minutes',
                        'body' => 'Charge devices, test your connection, set meeting reminders, and prep next-day attire. A short routine lowers stress and keeps your focus on work priorities.',
                    ],
                ],
                'conclusion' => 'Consistency is what makes business travel easier. Reuse the same document system, arrival checklist, and room setup on every trip. The routine takes only a few minutes but prevents forgotten details from competing with the work that brought you there.',
                'tags' => ['Productivity', 'Business Stay', 'Routine'],
                'author' => [
                    'name' => 'Nina Valdez',
                    'role' => 'Business Travel Columnist',
                ],
            ],
            [
                'slug' => 'smart-packing-guide-for-hotel-stays',
                'title' => 'A Smart Packing Guide For Hotel Stays',
                'excerpt' => 'Pack lighter without forgetting the essentials that make a hotel stay comfortable.',
                'image' => 'https://images.unsplash.com/photo-1553531384-cc64ac80f931?auto=format&fit=crop&w=1400&q=80',
                'date' => 'December 8, 2025',
                'category' => 'Packing Guide',
                'read_time' => '5 min read',
                'intro' => 'Packing well is less about bringing more and more about knowing what the hotel already provides. A simple system keeps your luggage manageable and your essentials easy to find.',
                'highlights' => [
                    'Check the hotel amenity list before packing duplicate toiletries or equipment.',
                    'Organize belongings by use so arrival and checkout both take less time.',
                    'Keep medication, documents, and one change of clothes in your carry-on.',
                ],
                'sections' => [
                    [
                        'heading' => 'Start with the hotel amenity list',
                        'body' => 'Review which toiletries, linens, appliances, and services are already available. Confirm anything important directly with the hotel, then remove unnecessary duplicates from your luggage.',
                    ],
                    [
                        'heading' => 'Pack around complete outfits',
                        'body' => 'Choose clothing by day and activity instead of adding individual pieces at random. A small coordinated wardrobe creates more combinations while keeping shoes and bulky items under control.',
                    ],
                    [
                        'heading' => 'Build one arrival pouch',
                        'body' => 'Group chargers, sleepwear, toiletries, and the next morning’s essentials together. When you reach the room, you can settle in quickly without unpacking every compartment.',
                    ],
                    [
                        'heading' => 'Prepare for a smooth checkout',
                        'body' => 'Use one consistent place for keys, documents, and small valuables throughout the stay. Repack non-essential items the night before and perform a final check of drawers, outlets, and the room safe.',
                    ],
                ],
                'conclusion' => 'Smart packing reduces decisions at every stage of a trip. Use the hotel amenity list, plan complete outfits, and organize around arrival and departure. You will carry less while keeping the things you genuinely need close at hand.',
                'tags' => ['Packing List', 'Travel Essentials', 'Organization'],
                'author' => [
                    'name' => 'Camille Flores',
                    'role' => 'Travel Organization Writer',
                ],
            ],
            [
                'slug' => 'stress-free-family-hotel-stay',
                'title' => 'How To Plan A Stress-Free Family Hotel Stay',
                'excerpt' => 'Simple room, packing, and arrival decisions that make traveling with children easier.',
                'image' => 'https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=1400&q=80',
                'date' => 'November 18, 2025',
                'category' => 'Family Travel',
                'read_time' => '7 min read',
                'intro' => 'Family trips run more smoothly when the hotel supports your everyday routines. A few specific questions before booking can prevent the most common space, sleep, and meal-time problems.',
                'highlights' => [
                    'Confirm the exact bed setup and maximum room occupancy before paying.',
                    'Choose a location that reduces daily transport and unnecessary transfers.',
                    'Keep the first evening simple so everyone can settle into the new routine.',
                ],
                'sections' => [
                    [
                        'heading' => 'Match the room to your family routine',
                        'body' => 'Think beyond the number of beds. Separate seating, blackout curtains, a refrigerator, and enough storage can matter more during a multi-night stay, especially when children have different sleep schedules.',
                    ],
                    [
                        'heading' => 'Ask the hotel specific questions',
                        'body' => 'Confirm crib availability, extra bedding fees, connecting-room policies, and child-friendly amenities in advance. Written confirmation makes arrival faster and gives the hotel time to prepare what you need.',
                    ],
                    [
                        'heading' => 'Pack a small arrival kit',
                        'body' => 'Keep pajamas, toiletries, medicine, chargers, and a familiar snack in one easy-to-reach bag. You can settle in without opening every suitcase after a tiring journey.',
                    ],
                    [
                        'heading' => 'Protect downtime in the itinerary',
                        'body' => 'Schedule fewer fixed activities than you would on an adults-only trip. A quiet afternoon at the hotel can prevent an overtired evening and gives everyone room to enjoy the destination at a comfortable pace.',
                    ],
                ],
                'conclusion' => 'A successful family stay is built around realistic routines, not a packed itinerary. Confirm the room details, organize the first night, and leave space for rest. Those choices give the whole family more energy for the experiences that matter.',
                'tags' => ['Family Stay', 'Packing', 'Room Planning'],
                'author' => [
                    'name' => 'Sofia Ramos',
                    'role' => 'Family Travel Contributor',
                ],
            ],
            [
                'slug' => 'rest-and-recharge-on-a-short-stay',
                'title' => 'How To Rest And Recharge On A Short Hotel Stay',
                'excerpt' => 'Create a restorative overnight escape with better boundaries, sleep habits, and pacing.',
                'image' => 'https://images.unsplash.com/photo-1602002418082-a4443e081dd1?auto=format&fit=crop&w=1400&q=80',
                'date' => 'October 30, 2025',
                'category' => 'Wellness',
                'read_time' => '6 min read',
                'intro' => 'Even one night away can feel restorative when you protect it from the habits that make ordinary days exhausting. The goal is not to fit in everything, but to create space for genuine recovery.',
                'highlights' => [
                    'Set one clear intention for the stay instead of filling every hour.',
                    'Prepare the room for sleep before you begin your evening activities.',
                    'Leave a gentle buffer between checkout and your next obligation.',
                ],
                'sections' => [
                    [
                        'heading' => 'Decide what rest means for this trip',
                        'body' => 'You may need uninterrupted sleep, quiet reading time, a long meal, or distance from work notifications. Naming the priority makes it easier to decline activities that would leave the stay feeling rushed.',
                    ],
                    [
                        'heading' => 'Set up the room as soon as you arrive',
                        'body' => 'Adjust the temperature, close the curtains, locate charging points, and request anything missing early. A comfortable room removes small interruptions later when you are ready to unwind.',
                    ],
                    [
                        'heading' => 'Create a low-stimulation evening',
                        'body' => 'Choose an unhurried dinner, dim screens, and prepare for the morning before bed. Familiar cues help your body settle even when you are sleeping in a new environment.',
                    ],
                    [
                        'heading' => 'Make checkout part of the reset',
                        'body' => 'Pack most belongings the night before and review the bill with time to spare. A calm departure preserves the benefit of the stay instead of replacing it with a final burst of stress.',
                    ],
                ],
                'conclusion' => 'A short stay can still create meaningful distance from a demanding routine. Protect one purpose, remove small sources of friction, and leave without rushing. Rest is more likely to last when the entire stay supports it.',
                'tags' => ['Rest', 'Sleep', 'Short Escape'],
                'author' => [
                    'name' => 'Elena Torres',
                    'role' => 'Wellness Editor',
                ],
            ],
        ];
    }
}
