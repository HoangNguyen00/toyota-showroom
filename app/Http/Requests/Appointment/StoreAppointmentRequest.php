<?php

namespace App\Http\Requests\Appointment;

use App\Enums\AppointmentType;
use App\Enums\CarStatus;
use App\Enums\ServiceType;
use App\Models\Car;
use App\Models\Service;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('contact_name'))) {
            $this->merge(['contact_name' => trim($this->input('contact_name'))]);
        }
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(AppointmentType::class)],
            'car_id' => ['nullable', 'integer'],
            'service_id' => ['nullable', 'integer'],
            'customer_car_info' => ['nullable', 'string', 'max:200'],
            'contact_name' => ['required', 'string', 'max:100'],
            'contact_phone' => ['required', 'string', 'regex:/^(0[3-9])\d{8}$/'],
            'requested_at' => ['required', 'date', 'after:now', 'before:+6 months'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => 'Vui lòng chọn loại lịch hẹn.',
            'type.Illuminate\Validation\Rules\Enum' => 'Loại lịch hẹn không hợp lệ.',
            'contact_name.required' => 'Vui lòng nhập họ tên liên hệ.',
            'contact_name.max' => 'Họ tên tối đa 100 ký tự.',
            'contact_phone.required' => 'Vui lòng nhập số điện thoại.',
            'contact_phone.regex' => 'Số điện thoại phải có 10 số và bắt đầu từ 03 đến 09.',
            'requested_at.required' => 'Vui lòng chọn thời gian mong muốn.',
            'requested_at.date' => 'Thời gian không hợp lệ.',
            'requested_at.after' => 'Thời gian mong muốn phải sau thời điểm hiện tại.',
            'requested_at.before' => 'Thời gian mong muốn trong vòng 6 tháng tới.',
            'customer_car_info.max' => 'Thông tin xe tối đa 200 ký tự.',
        ];
    }

    /**
     * Validate type-specific target fields and availability after base rules.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $type = $this->input('type');
            $carId = $this->input('car_id');
            $serviceId = $this->input('service_id');

            if ($type === AppointmentType::Viewing->value) {
                if (! $carId) {
                    $validator->errors()->add('car_id', 'Vui lòng chọn xe để đặt lịch xem.');

                    return;
                }
                $car = Car::where('id', $carId)->first();
                if (! $car || ! $car->is_visible || $car->category?->is_visible === false) {
                    $validator->errors()->add('car_id', 'Xe không tồn tại hoặc chưa hiển thị.');

                    return;
                }
                if ($car->status !== CarStatus::Available) {
                    $validator->errors()->add('car_id', 'Xe này hiện không nhận lịch xem.');
                }
            }

            if ($type === AppointmentType::Maintenance->value) {
                if (! $serviceId) {
                    $validator->errors()->add('service_id', 'Vui lòng chọn dịch vụ bảo dưỡng.');

                    return;
                }
                $service = Service::where('id', $serviceId)->first();
                if (! $service || ! $service->is_visible) {
                    $validator->errors()->add('service_id', 'Dịch vụ không tồn tại hoặc chưa hiển thị.');
                    if (trim((string) $this->input('customer_car_info')) === '') {
                        $validator->errors()->add('customer_car_info', 'Vui lòng nhập thông tin xe của bạn (mẫu xe/biển số).');
                    }

                    return;
                }
                if (! in_array($service->type, [ServiceType::Maintenance, ServiceType::Warranty], true)) {
                    $validator->errors()->add('service_id', 'Dịch vụ này không nhận đặt lịch.');

                    return;
                }
                if (! $service->allow_booking) {
                    $validator->errors()->add('service_id', 'Dịch vụ này hiện không nhận đặt lịch.');
                }
                if (trim((string) $this->input('customer_car_info')) === '') {
                    $validator->errors()->add('customer_car_info', 'Vui lòng nhập thông tin xe của bạn (mẫu xe/biển số).');
                }
                if ($this->filled('car_id')) {
                    $validator->errors()->add('car_id', 'Lịch bảo dưỡng không nhận car_id.');
                }
            }

            if ($type === AppointmentType::Viewing->value && $this->filled('customer_car_info')) {
                $validator->errors()->add('customer_car_info', 'Lịch xem xe không nhận thông tin xe riêng.');
            }
        });
    }
}
