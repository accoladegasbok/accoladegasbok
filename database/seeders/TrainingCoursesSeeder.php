<?php
// FILE: database/seeders/TrainingCoursesSeeder.php
//
// Starter training library for Auto Zenith Parts / Gasbok Engineering: 10 courses, each with a written lesson,
// a short company standard to accept, and a 6-question quiz. Safe to run again: a course whose title already
// exists is left alone (so your edits are never overwritten).
//
//   php artisan db:seed --class=TrainingCoursesSeeder --force
//
// These are a starting point. Edit any course in Staff Training > Courses, add a video, change the questions.

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TrainingCoursesSeeder extends Seeder
{
    public function run(): void
    {
        if (!Schema::hasTable('training_courses') || !Schema::hasColumn('training_courses', 'lesson_text')) {
            $this->command?->error('Run php artisan migrate --force first (training tables and lesson_text column).');
            return;
        }
        $made = 0;
        foreach ($this->courses() as $c) {
            if (DB::table('training_courses')->where('title', $c['title'])->exists()) continue;
            $id = DB::table('training_courses')->insertGetId([
                'title' => $c['title'], 'area' => $c['area'], 'description' => $c['description'],
                'provider_type' => 'internal', 'provider_name' => 'Auto Zenith Parts',
                'video_source' => 'none', 'duration_minutes' => $c['minutes'], 'pass_mark' => 80,
                'recurrence_days' => $c['repeat'], 'policy_text' => $c['policy'], 'lesson_text' => trim($c['lesson']),
                'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
            ]);
            foreach ($c['quiz'] as $i => [$q, $opts, $correct]) {
                // Mix the answer order (the same way every run) so the right answer is not always in the same place.
                $right = $opts[$correct];
                mt_srand(crc32($c['title'] . '|' . $q));
                shuffle($opts);
                $correct = array_search($right, $opts, true);
                DB::table('training_questions')->insert([
                    'course_id' => $id, 'question' => $q, 'options' => json_encode($opts, JSON_UNESCAPED_UNICODE),
                    'correct_index' => $correct, 'sort' => $i, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
            $made++;
        }
        $this->command?->info("Training library: {$made} course(s) added.");
    }

    private function courses(): array
    {
        return [

        // ───────────────────────────── 1. CUSTOMER SERVICE ─────────────────────────────
        [
            'title' => 'Customer Service Excellence', 'area' => 'Customer service', 'minutes' => 30, 'repeat' => 365,
            'description' => 'How we greet, listen to, advise and follow up with every customer - in the shop, on the phone and on WhatsApp.',
            'policy' => 'I will treat every customer politely and honestly, give accurate information, never guess a part fit, and follow up on every request.',
            'lesson' => <<<'TXT'
## Why it matters
Every customer chooses where to spend money. A customer who feels respected comes back and tells others. A customer who feels ignored tells even more people. Our reputation is built one conversation at a time.

## The five standards
- Greet quickly. Acknowledge every customer in the shop within one minute. Answer calls and messages as fast as you can. Our target for the first reply to a customer request is 2 hours.
- Listen first. Let the customer finish. Repeat back the vehicle (make, model, year, engine) and the part before you quote a price.
- Be accurate. Confirm the VIN or part number. Never guess a fitment. If you are not sure, say "let me confirm and call you back" and then do it.
- Be honest. State the condition, price, warranty and delivery time clearly. Never promise what we cannot deliver.
- Follow up. Every request gets a reply, every quote gets a follow-up, and every sale gets a thank-you and a check that the part fitted.

## Phone and WhatsApp
- Greet with the company name and your name.
- Write full, polite sentences. No rude words, and keep emojis light.
- Send photos of the actual part, and confirm price, location and delivery time in writing.
- Reply even when the answer is "we do not have it yet". Silence loses customers.

## When we do not have the part
Log it as a part request in the admin (Part Requests), tell the customer we will look for it, and follow up when it is found. A customer who is told "we will find it" and then hears back often becomes a loyal customer.

## Handling an angry customer
- Stay calm and polite. Do not argue or raise your voice.
- Let them explain everything without interrupting.
- Say sorry for the bad experience, then explain what you can do.
- Offer a solution within your authority. If it is beyond you, bring in your supervisor.
- Write down what happened so the team can learn from it.

## After the sale
Thank the customer, make sure they know the warranty and return terms, and check a few days later that the part fitted and works. This is customer follow-up, and it is one of the criteria for Employee of the Month.
TXT,
            'quiz' => [
                ['What is our target time for the first reply to a customer request?', ['Within 2 days', 'Within 2 hours', 'When we are less busy', 'Within one week'], 1],
                ['A customer asks if a part fits their car and you are not sure. What do you do?', ['Say yes so you do not lose the sale', 'Say no to be safe', 'Confirm with the VIN or part number first, then answer', 'Ask the customer to find out'], 2],
                ['Before quoting a part, what should you repeat back to the customer?', ['Only the price', 'Their name', 'The make, model, year, engine and the part wanted', 'The delivery date'], 2],
                ['We do not have the part a customer wants. What is the best action?', ['Tell them to go elsewhere', 'Log a part request, tell the customer we will search, and follow up', 'Ignore the message', 'Offer a different part without telling them'], 1],
                ['A customer is shouting about a problem. What should you do first?', ['Shout back', 'Walk away', 'Stay calm, let them explain, then apologise for the experience and offer a solution', 'Tell them they are wrong'], 2],
                ['After a sale is complete, what should happen?', ['Nothing, the job is finished', 'Thank the customer and check later that the part fitted', 'Ask for a discount on the next order', 'Delete the customer details'], 1],
            ],
        ],

        // ───────────────────────────── 2. WORKPLACE SAFETY ─────────────────────────────
        [
            'title' => 'Workplace Safety in the Shop, Store and Yard', 'area' => 'Safety', 'minutes' => 35, 'repeat' => 365,
            'description' => 'Staying safe around vehicles, tools, fluids, batteries, airbags and heavy parts.',
            'policy' => 'I will wear the required safety equipment, follow safe working rules, and report every injury, near-miss and unsafe condition immediately.',
            'lesson' => <<<'TXT'
## Protective equipment (PPE)
- Safety boots with steel or hard toe caps in the yard, store and workshop.
- Gloves when handling metal, glass, batteries and fluids.
- Eye protection when cutting, grinding, using impact tools or working under a vehicle.
- Hearing protection near loud tools. A dust mask in dusty or fume areas.
- High-visibility vest in the yard where vehicles move.

## Main hazards in a parts business
- Sharp metal and broken glass when dismantling vehicles.
- Fluids: engine oil, coolant, brake fluid, fuel and transmission oil. They are slippery, harmful to skin and some catch fire.
- Batteries: acid burns, sparks and explosive gas. Never lay tools across the terminals.
- Airbags and seat-belt pretensioners (SRS). An undeployed airbag can fire and injure you. Only trained staff remove them, with the battery disconnected and after the waiting time the manufacturer gives.
- Hybrid and electric vehicles: high-voltage parts and thick orange cables can kill. Never touch or cut them unless you are trained and authorised.
- Air-conditioning gas: never release it into the air.
- Heavy parts such as engines, gearboxes and doors.

## Working under or around vehicles
- Never rely on a jack alone. Use proper jack stands on firm, level ground.
- Chock the wheels and keep the vehicle in gear or park.
- Let exhaust and engine parts cool before touching them.
- No smoking, flames or sparks near fuel, batteries or oily rags.

## Lifting
- Bend your knees, keep your back straight, hold the load close to your body.
- Use a hoist, trolley or a second person for heavy items like engines and transmissions.
- Never lift something you cannot control.

## Fire
- Know where the extinguishers and exits are.
- To use an extinguisher remember PASS: Pull the pin, Aim at the base of the fire, Squeeze the handle, Sweep side to side.
- Never use water on a fuel or electrical fire.
- If the fire is not small and under control, leave the area and call for help.

## Spills and waste
Stop the leak, cover the spill with sand or absorbent, sweep it up and dispose of it properly. Never wash oil, coolant or fuel into drains or the ground.

## Reporting
Report every injury, near-miss and unsafe condition to your supervisor immediately, even if it seems small. Know where the first aid kit is. Reporting is how we stop the next accident.
TXT,
            'quiz' => [
                ['What must be true before anyone removes an undeployed airbag?', ['Nothing, it is safe', 'Only trained staff do it, with the battery disconnected and the waiting time observed', 'Anyone can do it with gloves', 'It must be done while the engine is running'], 1],
                ['Which is the safe way to work under a vehicle?', ['Rely on the jack only', 'Use proper jack stands on firm, level ground', 'Put a block of wood under the jack', 'Work quickly so the jack does not matter'], 1],
                ['What does PASS mean when using a fire extinguisher?', ['Push, Aim, Spray, Stop', 'Pull, Aim, Squeeze, Sweep', 'Point, Attack, Spray, Soak', 'Pull, Alert, Shout, Stop'], 1],
                ['You see thick orange cables on a hybrid vehicle. What should you do?', ['Cut them to disconnect the battery', 'Touch them only with rubber gloves', 'Leave them alone unless you are trained and authorised', 'Pull them out of the way'], 2],
                ['You are asked to move an engine. What is the correct way?', ['Lift it alone quickly', 'Use a hoist or trolley or get more people', 'Drag it across the floor', 'Ask a customer to help'], 1],
                ['Engine oil is spilled on the floor. What do you do?', ['Wash it into the drain', 'Leave it for the night staff', 'Cover it with absorbent, sweep it up and dispose of it properly', 'Cover it with cardboard'], 2],
            ],
        ],

        // ───────────────────────────── 3. BASIC CAR PARTS ─────────────────────────────
        [
            'title' => 'Basic Car Parts Knowledge', 'area' => 'Parts knowledge', 'minutes' => 40, 'repeat' => null,
            'description' => 'The main systems of a car and the parts in each, plus how to describe a part correctly.',
            'policy' => 'I will use the correct part names, confirm side, year, engine and transmission before selling, and never guess.',
            'lesson' => <<<'TXT'
## Engine
- Engine block, cylinder head, pistons, crankshaft and camshaft are the core of the engine.
- The timing belt or timing chain keeps the crankshaft and camshaft in step.
- Oil pan, oil pump and oil filter look after lubrication. Gaskets seal the joints.
- Intake manifold brings air in. The exhaust manifold takes gases out.

## Fuel and air
- Fuel tank, fuel pump, fuel filter and fuel injectors deliver fuel.
- Air filter, throttle body and the mass airflow (MAF) sensor control the air.

## Cooling
- Radiator, water pump, thermostat, cooling fan and hoses keep the engine at the right temperature. The thermostat opens when the engine is warm.

## Electrical
- Battery stores power. The alternator charges the battery while the engine runs. The starter motor turns the engine to start it.
- The ECU (engine computer), fuse box, wiring harness and sensors control and monitor everything.

## Transmission and drivetrain
- Gearbox (manual or automatic), clutch, torque converter, driveshaft, CV axle, differential and transfer case (on 4x4) move power to the wheels.

## Suspension and steering
- Shock absorbers, struts, springs, control arms, ball joints, bushings and sway-bar links.
- Steering rack, tie-rod ends and the power steering pump or electric steering motor.

## Brakes
- Brake pads, rotors (discs), calipers, brake lines, master cylinder, brake booster and the ABS module.

## Body and interior
- Bumpers, fenders, hood, doors, trunk or tailgate, grille, headlights, tail lights, mirrors and glass.
- Dashboard, seats, instrument cluster, radio, airbags and trim.

## Exhaust
- Catalytic converter, muffler, pipes and oxygen sensors.

## Describing a part correctly
- Left and right are always from the driver's seat looking forward. Say front or rear too, for example "front left".
- Fit depends on year, make, model, engine, transmission and trim. Two cars of the same model can use different parts.
- Check the number on the part itself. OEM numbers and interchange information help, but always confirm.
- When unsure, check the VIN or ask a supervisor. A wrong part costs us a return and the customer's trust.
TXT,
            'quiz' => [
                ['Which part charges the battery while the engine is running?', ['Starter motor', 'Alternator', 'Radiator', 'Thermostat'], 1],
                ['What does the starter motor do?', ['Charges the battery', 'Turns the engine over to start it', 'Cools the engine', 'Filters the air'], 1],
                ['"Front left" part is described from which point of view?', ['From the front of the car looking at it', 'From a person standing behind the car', 'From the driver\'s seat looking forward', 'It does not matter'], 2],
                ['Which of these belongs to the suspension?', ['Control arm', 'Alternator', 'Fuel injector', 'Catalytic converter'], 0],
                ['The thermostat is part of which system?', ['Brake system', 'Cooling system', 'Exhaust system', 'Steering system'], 1],
                ['Where is the catalytic converter found?', ['In the exhaust system', 'In the cooling system', 'Inside the gearbox', 'In the fuel tank'], 0],
                ['Two cars have the same model name but different engines. What is true?', ['The same parts always fit', 'Some parts may not fit, so check year, engine and transmission', 'Only body parts differ', 'Parts fit if the colour matches'], 1],
            ],
        ],

        // ───────────────────────────── 4. VIN DECODING ─────────────────────────────
        [
            'title' => 'VIN Decoding', 'area' => 'Parts knowledge', 'minutes' => 30, 'repeat' => null,
            'description' => 'How to read a 17-character Vehicle Identification Number and use it to confirm the right part.',
            'policy' => 'I will decode the VIN before quoting a part that depends on year, engine or trim, and I will not guess.',
            'lesson' => <<<'TXT'
## What a VIN is
A Vehicle Identification Number (VIN) is a 17-character code that identifies one vehicle. Cars built from 1981 use this format. The letters I, O and Q are never used, so a character that looks like O is a zero and one that looks like I is a one.

## Where to find it
- At the base of the windshield on the driver's side, visible from outside.
- On the sticker inside the driver's door jamb.
- On the registration or title papers and often in the engine bay.
Always compare at least two places. If they do not match, stop and tell a supervisor.

## The structure
- Characters 1 to 3: the World Manufacturer Identifier. It tells you the country and the maker.
- Characters 4 to 8: the vehicle description. This is where model, body, engine and restraint system are coded. The engine is usually coded in this part, so it is very important for parts.
- Character 9: the check digit. It confirms the VIN is valid.
- Character 10: the model year.
- Character 11: the assembly plant.
- Characters 12 to 17: the serial number.

## First character: country
- 1, 4 or 5: USA.
- 2: Canada. 3: Mexico.
- J: Japan. K: South Korea.
- W: Germany. S: United Kingdom.

## Example maker codes (first three characters)
- 1HG: Honda (USA). JT: Toyota (Japan). KMH: Hyundai (Korea).
- WBA: BMW. WDB: Mercedes-Benz. 1FA: Ford.

## Model year (character 10)
A=2010, B=2011, C=2012, D=2013, E=2014, F=2015, G=2016, H=2017, J=2018, K=2019, L=2020, M=2021, N=2022, P=2023, R=2024, S=2025, T=2026, V=2027. Earlier years: 1 to 9 for 2001 to 2009. The pattern repeats every 30 years.

## How we use it
- Decode the VIN before quoting any part that depends on year, engine, transmission or trim.
- Use the VIN decoder in the admin and record the VIN when we harvest a donor vehicle.
- Write the VIN carefully. One wrong character gives a wrong answer.
- Use the vehicle's own label or the part number to double-check. Never sell a fit-critical part on a guess.
TXT,
            'quiz' => [
                ['How many characters are in a modern VIN?', ['10', '15', '17', '20'], 2],
                ['Which letters are never used in a VIN?', ['A, B, C', 'I, O, Q', 'X, Y, Z', 'E, F, G'], 1],
                ['Which character of the VIN shows the model year?', ['The 1st', 'The 5th', 'The 10th', 'The 17th'], 2],
                ['A VIN that starts with J was built in which country?', ['USA', 'Japan', 'Germany', 'Mexico'], 1],
                ['A car built after 2010 has the letter K as its 10th character. What is its model year?', ['2011', '2014', '2019', '2022'], 2],
                ['Where can you usually see the VIN without opening the car?', ['On the tyre', 'At the base of the windshield, driver side', 'Under the back seat', 'On the radio'], 1],
                ['The VIN on the windshield does not match the door sticker. What do you do?', ['Use either one', 'Stop and tell a supervisor', 'Use the longer one', 'Ignore it'], 1],
            ],
        ],

        // ───────────────────────────── 5. CAR BRANDS ─────────────────────────────
        [
            'title' => 'Car Brands and Who Owns Them', 'area' => 'Parts knowledge', 'minutes' => 35, 'repeat' => null,
            'description' => 'The major brands, their luxury partners and shared platforms, so we search and advise correctly.',
            'policy' => 'I will not assume a part fits just because two cars share a brand family; I will check year, engine and transmission.',
            'lesson' => <<<'TXT'
## Why we learn brands
Many brands share engines, gearboxes and platforms. Knowing the families helps us find the right interchange and answer customers faster. But sharing never means every part fits, so we always confirm.

## Japanese brands
- Toyota is the biggest seller in our markets. Lexus is Toyota's luxury brand. Scion was a youth brand that ended in 2016. Camry and Lexus ES share a platform. Highlander and Lexus RX are related.
- Honda and its luxury brand Acura. Accord and the Acura TLX or older TSX are related. Odyssey and Pilot share a base.
- Nissan and its luxury brand Infiniti. Altima, Maxima, Rogue and Pathfinder are common.
- Mazda, Subaru, Mitsubishi and Suzuki are separate makers.

## American brands
- Ford and its luxury brand Lincoln. Mercury was closed in 2011.
- General Motors owns Chevrolet, GMC, Buick and Cadillac.
- Stellantis owns Chrysler, Dodge, Jeep and Ram, and also Fiat and Peugeot.

## Korean brands
- The Hyundai Motor Group owns Hyundai, Kia and Genesis. Sonata and Kia Optima are related. Santa Fe and Sorento are related.

## European brands
- Mercedes-Benz (Germany).
- BMW Group owns BMW, Mini and Rolls-Royce.
- Volkswagen Group owns VW, Audi, Porsche, Skoda, SEAT, Bentley and Lamborghini.
- Peugeot and Renault are French.

## Useful habits
- Always ask for the VIN, not just "a Camry".
- The same model can differ by country. A US-market car and a Japan-market car of the same name can use different parts.
- Luxury brand versions use many parts shared with their mainstream partner, but trim, sensors and wiring can differ.
- Write the brand and model exactly as the system lists them.
TXT,
            'quiz' => [
                ['Which brand is Toyota\'s luxury brand?', ['Acura', 'Infiniti', 'Lexus', 'Genesis'], 2],
                ['Which brand is Honda\'s luxury brand?', ['Acura', 'Lincoln', 'Lexus', 'Buick'], 0],
                ['Which brand is Nissan\'s luxury brand?', ['Cadillac', 'Infiniti', 'Genesis', 'Audi'], 1],
                ['Hyundai, Kia and Genesis belong to which group?', ['Stellantis', 'General Motors', 'Hyundai Motor Group', 'Volkswagen Group'], 2],
                ['Audi belongs to which group?', ['BMW Group', 'Volkswagen Group', 'Ford', 'Toyota'], 1],
                ['Two models share a platform. What should you do before selling a part from one for the other?', ['Assume it fits', 'Check year, engine and transmission and confirm the part', 'Ask the customer to try it', 'Sell it with no warranty'], 1],
                ['Chevrolet, GMC, Buick and Cadillac are owned by which company?', ['Ford', 'General Motors', 'Stellantis', 'Mazda'], 1],
            ],
        ],

        // ───────────────────────────── 6. AUTOMOTIVE TECHNOLOGIES ─────────────────────────────
        [
            'title' => 'Automotive Technologies for Parts Staff', 'area' => 'Parts knowledge', 'minutes' => 45, 'repeat' => null,
            'description' => 'Engine computers, sensors, transmissions, safety systems, hybrids and why electronic parts need extra care.',
            'policy' => 'I will handle electronic and high-voltage parts with care and sell them only after checking that they match the vehicle.',
            'lesson' => <<<'TXT'
## Engine management
- The engine computer is called the ECU, ECM or PCM. It reads sensors and controls injectors, ignition and the throttle.
- Common sensors: mass airflow (MAF), manifold pressure (MAP), oxygen (O2), crankshaft and camshaft position, coolant temperature, knock.
- Fuel injection can be port injection or direct injection (GDI). A turbocharger uses exhaust gas to force more air into the engine for more power.
- Variable valve timing (VVT, VTEC, VVT-i and similar) changes valve timing to improve power and economy.

## Diagnostics
- Cars since the late 1990s have an OBD-II port. A scan tool reads trouble codes such as P0300.
- A code points to a system or circuit that needs testing. It does not automatically mean the named part is bad. Never tell a customer to replace a part just because of a code.

## Transmissions
- Manual: a clutch connects engine and gearbox.
- Automatic: a torque converter and gear sets, controlled by a transmission computer.
- CVT: a belt and pulleys with no fixed gears.
- Dual-clutch (DCT): two clutches for quick shifts.
- Transmissions have codes (for example U241E). The code and the ECU, connector and pin count must match the vehicle.

## Electronics and networks
- Modules talk to each other over a network called CAN bus.
- Some ECUs, key modules and immobilizers must be matched or programmed to the vehicle. A used ECU from another car may not start the car without coding.
- Electronic parts can be ruined by static and wrong voltage. Handle them carefully and keep them in antistatic bags.

## Safety systems
- ABS stops wheels locking. Stability control helps prevent skids. TPMS watches tyre pressure.
- Airbags (SRS) and seat-belt pretensioners are explosive. Only trained staff handle them.
- ADAS (cameras, radar, parking sensors): after replacing a windshield, bumper or mirror these often need calibration.

## Hybrid and electric vehicles
- They have a high-voltage battery, an inverter and orange cables. These can cause fatal shocks.
- Only trained and authorised staff may work on them or remove high-voltage parts.

## Emissions
- The catalytic converter, EGR valve and oxygen sensors clean the exhaust. Diesel engines may also have a particulate filter (DPF).

## Why it matters for sales
Electronic parts are the most common cause of returns. Match the exact part number, year, engine, transmission and connector. Check the pin count where we have recorded it. Tell the customer when programming or coding may be needed.
TXT,
            'quiz' => [
                ['What does the ECU do?', ['Stores fuel', 'Reads sensors and controls engine functions', 'Cools the engine', 'Holds the battery'], 1],
                ['An OBD-II scan gives a trouble code. What does it mean?', ['Replace the named part at once', 'It points to a system or circuit to test, not automatically the bad part', 'The car is scrap', 'The battery is flat'], 1],
                ['What does a CVT transmission use instead of fixed gears?', ['Chains and pulleys only', 'A belt and pulleys', 'Two clutches', 'A shaft only'], 1],
                ['After replacing a windshield on a car with cameras, what often has to be done?', ['Nothing', 'Calibrate the camera system', 'Replace the ECU', 'Change the oil'], 1],
                ['What are thick orange cables on a vehicle a sign of?', ['Audio system', 'High-voltage hybrid or electric system', 'Fuel system', 'Cooling system'], 1],
                ['What is CAN bus?', ['A type of fuel can', 'A network that lets the car\'s modules talk to each other', 'A brake part', 'An engine gasket'], 1],
                ['Why must we match the exact part number and connector on an electronic part?', ['It makes the part look better', 'Electronic parts are a common cause of returns when they do not match', 'It lowers the price', 'It is not important'], 1],
            ],
        ],

        // ───────────────────────────── 7. BASIC ACCOUNTING ─────────────────────────────
        [
            'title' => 'Basic Accounting and Cash Handling for Staff', 'area' => 'Accounting', 'minutes' => 45, 'repeat' => 365,
            'description' => 'Recording sales, profit versus markup, handling cash and transfers, and the end-of-day cash check.',
            'policy' => 'I will record every sale in the system, verify payments before releasing goods, and report any cash difference the same day.',
            'lesson' => <<<'TXT'
## Every sale is recorded
- Every sale must have an invoice or receipt in the system. We never sell "off the book".
- Check the customer name, items, quantity, price, discount and currency before you confirm.
- Do not change a receipt after it is issued. Corrections go through a supervisor or manager.

## Revenue, cost and profit
- Revenue is what the customer pays. Cost is what we paid for the item and its handling. Profit = revenue minus cost.
- Markup is profit divided by cost. Margin is profit divided by selling price.
- Example: cost 40,000 and selling price 50,000. Profit is 10,000. Markup is 25%. Margin is 20%.
- Never sell below the approved price without authority.

## Cash and payments
- Count cash in front of the customer. Give the receipt immediately.
- For bank transfers, confirm the money is in our account (bank app or statement) before releasing goods. A screenshot or an SMS from the customer is not proof.
- Do not accept payment into a personal account.
- Keep cash in the cash box or safe, not in your pocket or drawer.

## Currency
- Nigerian branches work in naira. US branches work in US dollars. Use the system price. Do not convert by guess.

## Deposits, credit and refunds
- Part payments are recorded with a receipt that shows the balance.
- Customer credit is recorded in the credit ledger. Refunds go through the returns process with approval, never directly from the till.

## End-of-day cash check
- Expected cash = opening cash + cash sales - cash refunds - approved expenses paid out.
- Count the actual cash. If the count does not match, report the difference and the reason the same day. Never hide a shortage or keep an overage.
- Expenses need a receipt or approved voucher.

## Stock is money
Parts on the shelf are cash tied up. Receive stock into the system immediately, count it, and keep locations correct so that sales and stock always agree.
TXT,
            'quiz' => [
                ['Cost is 40,000 and the selling price is 50,000. What is the margin?', ['10%', '20%', '25%', '40%'], 1],
                ['Using the same figures, what is the markup?', ['20%', '25%', '10%', '50%'], 1],
                ['Opening cash is 100,000, cash sales are 250,000 and cash refunds are 30,000. What is the expected closing cash?', ['380,000', '350,000', '320,000', '280,000'], 2],
                ['A customer shows a transfer screenshot. What do you do before releasing the goods?', ['Release them, the screenshot is enough', 'Confirm the money is in our account', 'Ask them to send it again', 'Let them pay next week'], 1],
                ['Should every sale be recorded in the system?', ['Only large sales', 'Only card sales', 'Yes, every sale', 'Only when the manager is present'], 2],
                ['The closing cash count is short. What should you do?', ['Quietly add your own money', 'Say nothing and hope it balances tomorrow', 'Report the difference and the reason the same day', 'Reduce the sales figure'], 2],
            ],
        ],

        // ───────────────────────────── 8. NEGOTIATION & WAYBILLS ─────────────────────────────
        [
            'title' => 'Negotiation Skills: Buying and Selling Transport Services with Waybills', 'area' => 'Logistics', 'minutes' => 45, 'repeat' => null,
            'description' => 'How to prepare, negotiate and document transport deals as seller or buyer, and how to use a waybill properly.',
            'policy' => 'I will agree terms in writing before goods move, complete the waybill fully, and record any loss or damage the same day.',
            'lesson' => <<<'TXT'
## What a waybill is
A waybill is the transport document that travels with the goods. It names the sender and receiver, describes the goods, shows the quantity or weight, the origin and destination, the charges and who pays, the vehicle and driver and the date. It is a receipt for the goods and evidence of the transport agreement. It is not proof that someone owns the goods.

## Two roles for us
- Seller of transport: our logistics business quotes and moves customers' goods.
- Buyer of transport: we hire carriers to move parts between branches and to customers.

## Prepare before you negotiate
- Know the route, weight or volume, how fragile the goods are, and how urgent they are.
- Get at least two or three quotes so you know the market rate.
- Know your walk-away point: the least you will accept as a seller, or the most you will pay as a buyer. Know what you will do if the deal fails.
- List all costs: fuel, driver, tolls and levies, loading and unloading, insurance, waiting time.

## Selling transport services
- Sell the value (safe, on time, tracked), not just the price.
- State exactly what is included and what is extra.
- Never give a discount for nothing. Trade it: lower price for volume, advance payment or flexible dates.
- Get the true weight and declared value. Offer insurance for valuable goods.
- Use clear payment terms. New customers pay in advance or pay a deposit.

## Buying transport services
- Compare quotes on price, transit time, tracking, liability for loss or damage and proof of delivery.
- Check the carrier's identity, vehicle and plate number. Photograph the goods at loading.
- Do not pay everything in advance to an unknown carrier. Pay a deposit and the balance on safe delivery.
- Use repeat business and prompt payment as bargaining points.

## Good negotiating habits
- Ask questions and listen before you talk about price.
- Open with a reasonable first offer. Do not insult the other side with an extreme number.
- Exchange concessions: "If you can do X, then I can do Y."
- Stay calm and respectful. Never lie about weight, value or condition.
- Confirm every agreement in writing (WhatsApp message or signed document) before the goods are loaded.

## Waybill discipline
- Fill in every field before the goods leave. Count the packages.
- At pickup and delivery, write any damage or shortage on the waybill and photograph it.
- Keep the signed copy. Match it to the transfer record in the system.
- Report damage or loss the same day, with the waybill and photos, within the carrier's claim time.

## Warning signs
- The receiver is not named or cannot be reached.
- A price far below the market.
- The driver or vehicle changes at the last minute.
- A request to release goods without a waybill or payment terms.
TXT,
            'quiz' => [
                ['What is a waybill?', ['A tax receipt', 'The transport document describing the goods, parties, route and charges, acting as a receipt and evidence of the agreement', 'A customer complaint form', 'A bank statement'], 1],
                ['What should you know before you start negotiating?', ['Nothing, decide while talking', 'Market rates from several quotes and your walk-away point', 'Only the customer\'s name', 'The carrier\'s favourite colour'], 1],
                ['A customer asks you for a discount on transport. What is the best approach?', ['Refuse to talk', 'Give a big discount at once', 'Trade the discount for something such as volume or advance payment', 'Add a hidden charge'], 2],
                ['You notice damage when goods are delivered. What do you do?', ['Say nothing', 'Write it on the waybill, take photos and report it the same day', 'Wait a week to see if it gets worse', 'Refuse payment without telling anyone'], 1],
                ['An unknown carrier asks for 100% payment in advance. What is the safest response?', ['Pay everything immediately', 'Verify the carrier and vehicle, pay a deposit only, and put terms in writing', 'Hand over the goods without a waybill', 'Let the carrier decide the price'], 1],
                ['When should the agreed terms be confirmed in writing?', ['After delivery', 'Before the goods are loaded', 'Never', 'Only if there is a problem'], 1],
            ],
        ],

        // ───────────────────────────── 9. MUTUAL RESPECT ─────────────────────────────
        [
            'title' => 'Mutual Respect at Work', 'area' => 'Company policies', 'minutes' => 30, 'repeat' => 365,
            'description' => 'How we treat colleagues, customers and suppliers, and what to do about disrespect, harassment or conflict.',
            'policy' => 'I will treat everyone with respect, I will not harass, bully or discriminate, and I will report any such behaviour I see or experience.',
            'lesson' => <<<'TXT'
## Our standard
We treat everyone with dignity: colleagues, customers, suppliers and visitors. That includes every tribe, religion, gender, age, background and position. A team that respects each other works faster, makes fewer mistakes and keeps good people.

## What respect looks like
- Greet people, say please and thank you.
- Listen without interrupting. Disagree politely and give reasons.
- Be on time and do what you promised. Being unreliable disrespects the team.
- Credit people for their work. Share knowledge and help new colleagues.
- Accept feedback calmly and give it privately and kindly.

## What we do not tolerate
- Harassment of any kind, including unwanted sexual attention, comments or touching.
- Bullying, shouting, insults, threats and intimidation.
- Discrimination or jokes about someone's tribe, religion, gender, appearance or background.
- Spreading rumours or humiliating people, including in WhatsApp groups.
- Punishing someone for making a report.
- Any violence.

## Disagreements and conflict
- Take a breath. Speak privately and calmly about the issue, not the person.
- Listen to their side and look for a fair solution.
- If you cannot agree, ask your supervisor to help. If that does not work, go to the manager.
- Never settle a dispute with a fight or in front of customers.

## Online conduct
The same rules apply in WhatsApp groups, calls and social media. Do not share customer details, post staff photos without permission, or send offensive messages.

## Supervisors and managers
Lead by example. Treat people fairly, avoid favouritism, give clear instructions and listen to concerns. Take every report seriously.

## Reporting
If you experience or see disrespect, harassment or bullying, tell your supervisor or manager. If the problem involves them, go to the next level up. Reports are handled in confidence and nobody will be punished for reporting honestly.

## Difficult customers
A rude customer does not change our standard. Stay professional, do not respond with insults, and bring in your supervisor if you feel unsafe or the behaviour continues.
TXT,
            'quiz' => [
                ['Which of these is NOT acceptable at work?', ['Disagreeing politely', 'Shouting insults at a colleague', 'Asking for help', 'Giving private feedback'], 1],
                ['You disagree with a colleague. What is the best first step?', ['Argue in front of customers', 'Speak to them privately and calmly about the issue', 'Post about it in the group chat', 'Stop talking to them'], 1],
                ['You experience harassment. What should you do?', ['Keep quiet', 'Report it to your supervisor or manager', 'Take revenge', 'Leave without telling anyone'], 1],
                ['Do respect rules apply in WhatsApp groups?', ['No, it is not work', 'Yes, the same rules apply', 'Only when a manager is in the group', 'Only on weekdays'], 1],
                ['What should supervisors do?', ['Treat favourites better', 'Lead by example and treat people fairly', 'Ignore complaints', 'Avoid giving feedback'], 1],
                ['A customer is rude to you. What is the right response?', ['Insult them back', 'Stay professional and call your supervisor if it continues', 'Refuse to serve anyone', 'Post about them online'], 1],
            ],
        ],

        // ───────────────────────────── 10. LOSS PREVENTION ─────────────────────────────
        [
            'title' => 'Loss Prevention: Protecting Stock and Cash', 'area' => 'Company policies', 'minutes' => 35, 'repeat' => 365,
            'description' => 'How stock and money go missing, and the daily habits that stop it.',
            'policy' => 'I will not remove stock or cash without proper records, I will report anything suspicious, and I understand that failure to follow these rules can lead to disciplinary action.',
            'lesson' => <<<'TXT'
## Where losses come from
- Theft by customers, staff or suppliers.
- Mistakes: wrong counts, wrong prices, wrong items picked.
- Weak process: transfers or returns that were never recorded.
- Fraud: fake payment alerts, return fraud, collusion.
- Damage from poor handling or storage.

## The golden rules
- Every item has a code and a location in the system. Keep both correct.
- Nothing leaves the store without a receipt, a transfer waybill or a written approval.
- No selling off the book. No "borrowing" stock, even for a day.
- Keep the store room, yard gates and cash box locked. Control keys and know who holds them.
- Visitors and delivery drivers are accompanied.
- Keep high-value items (engines, transmissions, ECUs, catalytic converters, electronics) in locked areas.

## Receiving stock
- Count in front of the driver against the supplier document.
- Record the stock in the system immediately. Note any shortage or damage on the document before the driver leaves.

## Stock counts
- Count high-value items often and everything regularly.
- The count is best done by someone other than the person who keeps the stock.
- Investigate every difference. Never adjust figures quietly to hide it.

## Payments
- Confirm transfers in the bank app or statement before releasing goods. Beware of fake alert messages and screenshots.
- Never accept payment into a personal account.
- Refunds only through the returns process with approval from a supervisor or manager.

## Transfers between branches
- Create the transfer in the system and print the waybill.
- Count the units at dispatch and again at receipt.
- Never sign for something you have not counted.

## Returns
Inspect the part, compare it with what was sold, check the date and the warranty, and record the return. No cash refunds without approval.

## Conflict of interest
Do not sell to yourself, family or friends at special prices, or run a side business with company stock, without written manager approval.

## Reporting and consequences
- If you see someone taking items without paperwork, or anything that looks wrong, tell your supervisor or manager. Reports are confidential.
- Not reporting a theft you know about is itself a breach.
- Losses lead to investigation, recovery of the loss and disciplinary action, and legal action where needed.
TXT,
            'quiz' => [
                ['When may an item leave the store?', ['Whenever a staff member asks', 'Only with a receipt, a transfer waybill or written approval', 'If it is just for a day', 'If a customer insists'], 1],
                ['A shipment arrives. What is the correct way to receive it?', ['Sign and count later', 'Count it against the supplier document in front of the driver and record it immediately', 'Let the driver count', 'Record it next week'], 1],
                ['Who should ideally do a stock count?', ['Only the person who keeps the stock', 'Someone other than the stock keeper', 'The customer', 'Nobody'], 1],
                ['You receive a payment alert SMS. What should you do before releasing goods?', ['Release goods, the SMS is enough', 'Check the bank app or statement to confirm the money arrived', 'Ask the customer if it is real', 'Release half the goods'], 1],
                ['A customer wants a cash refund. What is the correct process?', ['Give cash from the till', 'Use the returns process with approval from a supervisor or manager', 'Tell them refunds are never possible', 'Refund from your own pocket'], 1],
                ['You see a colleague taking parts without any paperwork. What should you do?', ['Ignore it', 'Report it to your supervisor or manager', 'Join them', 'Post about it online'], 1],
                ['Is it allowed to borrow stock "just for a day" without a record?', ['Yes, if you return it', 'No, nothing leaves without records', 'Yes, if the shop is quiet', 'Yes, for family'], 1],
            ],
        ],

        ];
    }
}
