<?php

namespace App\Notifications;

use App\Models\Books;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookDegraded extends Notification
{
    use Queueable;

    protected $book;

    /**
     * Create a new notification instance.
     */
    public function __construct(Books $book)
    {
        $this->book = $book;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Alerte : Livre dégradé - ' . $this->book->title)
            ->line('Le livre suivant a été marqué comme ayant des exemplaires dégradés.')
            ->line('Titre : ' . $this->book->title)
            ->line('Auteur : ' . $this->book->author)
            ->line('Quantité dégradée actuelle : ' . $this->book->degraded_quantity)
            ->line('Quantité totale : ' . $this->book->total_quantity)
            ->action('Voir les détails', url('/api/books/' . $this->book->id))
            ->line('Merci de vérifier l\'état de la collection.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'book_id' => $this->book->id,
            'title' => $this->book->title,
            'degraded_quantity' => $this->book->degraded_quantity,
        ];
    }
}
