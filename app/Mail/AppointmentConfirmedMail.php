<?php

namespace App\Mail;

use App\Enums\AppointmentType;
use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Email xác nhận lịch hẹn (P2 đề xuất). Gửi trong SendAppointmentConfirmation, không tự xếp hàng.
 * Chỉ chứa thông tin khách cần để đến showroom; không ghi số điện thoại, ghi chú nội bộ hay nhân viên.
 */
class AppointmentConfirmedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Appointment $appointment) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Xác nhận lịch hẹn #'.$this->appointment->id.' — '.config('showroom.contact.name'));
    }

    public function content(): Content
    {
        $appointment = $this->appointment;
        $timezone = config('app.timezone');

        return new Content(
            view: 'emails.appointments.confirmed',
            text: 'emails.appointments.confirmed-text',
            with: [
                'appointment' => $appointment,
                'confirmedAt' => $appointment->confirmed_at->copy()->timezone($timezone),
                'timezone' => $timezone,
                'typeLabel' => $appointment->type === AppointmentType::Viewing ? 'Xem xe' : 'Bảo dưỡng',
                'target' => $appointment->type === AppointmentType::Viewing
                    ? implode(' · ', array_filter([$appointment->car?->name ?? 'Xe đã chọn', $appointment->car?->code]))
                    : ($appointment->service?->name ?? 'Dịch vụ đã chọn'),
                'showroom' => config('showroom.contact'),
                'manageUrl' => route('customer.appointments.index'),
            ],
        );
    }
}
