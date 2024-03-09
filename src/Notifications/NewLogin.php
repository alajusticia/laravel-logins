<?php

namespace ALajusticia\Logins\Notifications;

use ALajusticia\Logins\RequestContext;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\HtmlString;

class NewLogin extends Notification
{
    /**
     * Create a new notification instance.
     */
    public function __construct(
        private readonly RequestContext $context
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(mixed $notifiable): MailMessage
    {
        $deviceType = match ($this->context->parser()->getDeviceType()) {
            'desktop', 'mobile', 'phone', 'tablet' => __('logins::notifications.new_login.device_types.' . $this->context->parser()->getDeviceType()),
            default => __('logins::notifications.new_login.device_types.unknown'),
        };

        $mailMessage = (new MailMessage)
            ->subject(__('alajusticia/logins::notifications.new_login.subject'))
            ->line(__('alajusticia/logins::notifications.new_login.title'))
            ->line(__('alajusticia/logins::notifications.new_login.review_information'));

        $information = __('alajusticia/logins::notifications.new_login.device_type', ['value' => $deviceType]);

        if (! empty($this->context->parser()->getDevice())) {
            $information .= '<br>' . __('alajusticia/logins::notifications.new_login.device_name', ['value' => $this->context->parser()->getDevice()]);
        }

        $information .= '<br>' . __('alajusticia/logins::notifications.new_login.platform', ['value' => $this->context->parser()->getPlatform()]);
        $information .= '<br>' . __('alajusticia/logins::notifications.new_login.browser', ['value' => $this->context->parser()->getBrowser()]);
        $information .= '<br>' . __('alajusticia/logins::notifications.new_login.ip_address', ['value' => $this->context->ipAddress()]);

        if (! empty($this->context->location())) {
            $country = $this->context->location()->countryName ?? $this->context->location()->countryCode;
            if ($country) {
                $information .= '<br>' . __('alajusticia/logins::notifications.new_login.country', ['value' => $country]);
            }
        }

        $mailMessage
            ->line(new HtmlString($information))
            ->line(__('alajusticia/logins::notifications.new_login.not_you'));

        if ($securityPageRoute = Config::get('logins.security_page_route')) {
            $mailMessage->action(__('alajusticia/logins::notifications.new_login.check_security'), route($securityPageRoute));
        }

        return $mailMessage;
    }
}
