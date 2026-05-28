<?php

namespace App\Notifications;

use App\Models\User;
use App\Models\Validation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WorkflowTransitionNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected Validation $validation,
        protected string $libelleTransition,
        protected ?User $auteur = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $resource = class_basename($this->validation->validable_type);
        $action = $this->libelleTransition;

        return (new MailMessage)
            ->subject("[TB-PAPA-CEEAC] {$resource} : {$action}")
            ->greeting('Bonjour,')
            ->line("Une transition de workflow a été effectuée sur un {$resource} (ID #{$this->validation->validable_id}).")
            ->line("Action : **{$action}**")
            ->line("Auteur : {$this->auteur?->name}")
            ->when($this->validation->commentaire, fn ($m) => $m->line("Commentaire : « {$this->validation->commentaire} »"))
            ->action('Ouvrir l\'application', url('/dashboard'))
            ->salutation('Direction des Systèmes d\'Information — CEEAC');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'validation_id' => $this->validation->id,
            'validable_type' => $this->validation->validable_type,
            'validable_id' => $this->validation->validable_id,
            'etape' => $this->validation->etape,
            'decision' => $this->validation->decision,
            'libelle' => $this->libelleTransition,
            'auteur' => $this->auteur?->name,
            'commentaire' => $this->validation->commentaire,
        ];
    }
}
