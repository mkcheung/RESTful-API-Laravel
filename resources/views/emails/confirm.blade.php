Hello {{$user->name}}
You changed your email, so we need to verify the new address. Please use the link below:
{{route('verify', $user->verification_token)}}