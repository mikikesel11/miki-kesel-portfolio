<x-mail::message>
# New portfolio message

**From:** {{ $submission->name }} (<{{ $submission->email }}>)
**Received:** {{ $submission->created_at?->toDayDateTimeString() }}

{{ $submission->message }}

<x-mail::button :url="'mailto:'.$submission->email">
Reply
</x-mail::button>

Sent from your portfolio contact form.
</x-mail::message>
