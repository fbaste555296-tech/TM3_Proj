<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Book an Appointment | Triple-M3</title>

    <link
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">

    <link href="{{ asset('css/triple-m3.css') }}" rel="stylesheet">
    <link href="{{ asset('css/triple-m3-theme.css') }}" rel="stylesheet">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            padding: 35px 20px;
            background: #f6f5f3;
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: #242323;
        }

        .appointment-wrapper {
            width: min(100%, 680px);
            margin: 20px auto;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            margin-bottom: 18px;
            color: #88736d;
            text-decoration: none;
            font-size: 13px;
            font-weight: 800;
            transition: .2s;
        }

        .back-link:hover {
            color: #5e504c;
            transform: translateX(-2px);
        }

        .appointment-card {
            background: #ffffff;
            border: 1px solid #dedbd8;
            border-radius: 22px;
            padding: 42px;
            box-shadow: 0 20px 55px rgba(36, 35, 35, .09);
        }

        .appointment-header {
            margin-bottom: 30px;
        }

        .tm-eyebrow {
            margin: 0 0 8px;
            color: #88736d;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .14em;
        }

        .appointment-header h1 {
            margin: 0 0 10px;
            font-size: 34px;
            line-height: 1.15;
            letter-spacing: -.04em;
            color: #242323;
        }

        .appointment-header p {
            margin: 0;
            color: #6f6b69;
            font-size: 14px;
            line-height: 1.8;
        }

        .customer-name {
            color: #88736d;
            font-weight: 700;
        }

        .error-message {
            margin-bottom: 22px;
            padding: 13px 15px;
            border: 1px solid #dedbd8;
            border-radius: 10px;
            background: #eeecea;
            color: #5e504c;
            font-size: 13px;
            font-weight: 600;
        }

        .appointment-form {
            display: grid;
            gap: 22px;
        }

        .form-group {
            display: grid;
            gap: 8px;
        }

        .form-label {
            color: #242323;
            font-size: 13px;
            font-weight: 800;
        }

        .form-hint {
            margin-left: 5px;
            color: #777270;
            font-size: 11px;
            font-weight: 500;
        }

        .tm-input {
            width: 100%;
            min-height: 48px;
            padding: 13px 14px;
            border: 1px solid #dedbd8;
            border-radius: 11px;
            outline: none;
            background: #fdfdfc;
            color: #242323;
            font-family: inherit;
            font-size: 13px;
            transition: .2s;
        }

        .tm-input:focus {
            border-color: #88736d;
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(136, 115, 109, .14);
        }

        textarea.tm-input {
            min-height: 105px;
            resize: vertical;
        }

        select.tm-input {
            cursor: pointer;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .info-box {
            display: flex;
            align-items: flex-start;
            gap: 11px;
            margin-top: 2px;
            padding: 14px;
            border-radius: 12px;
            background: #eeecea;
            color: #5e5956;
            font-size: 11px;
            line-height: 1.6;
        }

        .info-icon {
            display: grid;
            place-items: center;
            flex-shrink: 0;
            width: 28px;
            height: 28px;
            border-radius: 8px;
            background: #dedbd8;
            color: #5e504c;
            font-size: 14px;
            font-weight: 800;
        }

        .submit-button {
            width: 100%;
            min-height: 50px;
            margin-top: 3px;
            border: 0;
            border-radius: 12px;
            background: #88736d;
            color: white;
            font-family: inherit;
            font-size: 13px;
            font-weight: 800;
            cursor: pointer;
            box-shadow: 0 10px 25px rgba(136, 115, 109, .20);
            transition: .2s;
        }

        .submit-button:hover {
            background: #5e504c;
            transform: translateY(-2px);
            box-shadow: 0 13px 28px rgba(136, 115, 109, .25);
        }

        .submit-button:active {
            transform: translateY(0);
        }

        .footer-note {
            margin-top: 20px;
            text-align: center;
            color: #777270;
            font-size: 11px;
            line-height: 1.6;
        }

        @media(max-width:600px) {
            body {
                padding: 20px 14px;
            }

            .appointment-wrapper {
                margin: 5px auto;
            }

            .appointment-card {
                padding: 27px 21px;
                border-radius: 18px;
            }

            .appointment-header h1 {
                font-size: 28px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

    <main class="appointment-wrapper">

        <a href="{{ route('home') }}" class="back-link">
            <img src="{{ asset('images/triplem3.jpg') }}" alt="" style="width:32px;height:32px;object-fit:contain;border-radius:50%">
            ← TRIPLE-M3
        </a>

        <section class="appointment-card">

            {{-- HEADER --}}
            <div class="appointment-header">

                <p class="tm-eyebrow">
                    APPOINTMENT REQUEST
                </p>

                <h1>
                    Book your service
                </h1>

                <p>
                    Hi <span class="customer-name">{{ $customer->name }}</span>,
                    tell us when and where you need help.
                    Our coordinator will confirm your appointment schedule.
                </p>

            </div>


            {{-- ERROR MESSAGE --}}
            @if($errors->any())

                <div class="error-message">
                    {{ $errors->first() }}
                </div>

            @endif


            {{-- APPOINTMENT FORM --}}
            <form
                method="POST"
                action="{{ route('appointments.store') }}"
                class="appointment-form">

                @csrf


                {{-- SERVICE --}}
                <div class="form-group">

                    <label class="form-label" for="service_type">
                        Service needed
                    </label>

                    <select
                        id="service_type"
                        name="service_type"
                        class="tm-input"
                        required>

                        <option value="Repair & Diagnostics"
                            {{ old('service_type') == 'Repair & Diagnostics' ? 'selected' : '' }}>
                            Repair & Diagnostics
                        </option>

                        <option value="Preventive Maintenance"
                            {{ old('service_type') == 'Preventive Maintenance' ? 'selected' : '' }}>
                            Preventive Maintenance
                        </option>

                        <option value="New Installation"
                            {{ old('service_type') == 'New Installation' ? 'selected' : '' }}>
                            New Installation
                        </option>

                        <option value="Inspection"
                            {{ old('service_type') == 'Inspection' ? 'selected' : '' }}>
                            Inspection
                        </option>

                    </select>

                </div>


                {{-- DATE --}}
                <div class="form-group">

                    <label class="form-label" for="scheduled_date">
                        Preferred date
                    </label>

                    <input
                        id="scheduled_date"
                        type="date"
                        name="scheduled_date"
                        min="{{ now()->toDateString() }}"
                        value="{{ old('scheduled_date') }}"
                        class="tm-input"
                        required>

                </div>

                <div class="form-group">
                    <label class="form-label" for="time_slot">Available time</label>
                    <select id="time_slot" name="time_slot" class="tm-input" required>
                        <option value="">Select a time slot</option>
                        @foreach(\App\Models\ServiceJob::TIME_SLOTS as $time => $label)
                            <option value="{{ $time }}" @selected(old('time_slot') === $time)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>


                {{-- ADDRESS --}}
                <div class="form-group">

                    <label class="form-label" for="address">
                        Service address
                    </label>

                    <textarea
                        id="address"
                        name="address"
                        class="tm-input"
                        placeholder="Enter the location where the service is needed..."
                        required>{{ old('address', $customer->address === 'To be confirmed' ? '' : $customer->address) }}</textarea>

                </div>


                {{-- NOTES --}}
                <div class="form-group">

                    <label class="form-label" for="notes">
                        Tell us about the issue
                        <span class="form-hint">optional</span>
                    </label>

                    <textarea
                        id="notes"
                        name="notes"
                        class="tm-input"
                        placeholder="Example: Aircon is not cooling, unusual noise, leaking water, etc.">{{ old('notes') }}</textarea>

                </div>


                {{-- INFORMATION --}}
                <div class="info-box">

                    <div class="info-icon">
                        ✓
                    </div>

                    <div>
                        After submitting your request, our coordinator
                        will review your preferred schedule and contact
                        you to confirm the appointment.
                    </div>

                </div>


                {{-- SUBMIT --}}
                <button type="submit" class="submit-button">
                    Submit Appointment Request →
                </button>

            </form>


            <p class="footer-note">
                Please provide accurate information so our team can
                properly prepare for your service request.
            </p>

        </section>

    </main>

</body>

</html>