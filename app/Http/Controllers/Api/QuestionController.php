<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuestionMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class QuestionController extends Controller
{
    public function store(Request $request, Quiz $quiz)
    {
        $this->authorize('update', $quiz);

        $validated = $request->validate([
            'question_text' => 'required|string',
            'type' => 'required|in:multiple_choice,true_false,short_answer',
            'explanation' => 'nullable|string',
        ]);

        $maxOrder = Question::where('quiz_id', $quiz->id)->max('order') ?? 0;

        $question = Question::create([
            'quiz_id' => $quiz->id,
            'question_text' => $validated['question_text'],
            'type' => $validated['type'],
            'order' => $maxOrder + 1,
            'explanation' => $validated['explanation'] ?? null,
        ]);

        return response()->json($question, 201);
    }

    public function update(Request $request, Question $question)
    {
        $this->authorize('update', $question->quiz);

        $validated = $request->validate([
            'question_text' => 'string',
            'type' => 'in:multiple_choice,true_false,short_answer',
            'explanation' => 'nullable|string',
            'order' => 'integer|min:1',
        ]);

        $question->update($validated);

        return response()->json($question);
    }

    public function destroy(Question $question)
    {
        $this->authorize('update', $question->quiz);
        $question->delete();
        return response()->json(['message' => 'Question deleted successfully']);
    }

    public function addMedia(Request $request, Question $question)
    {
        $this->authorize('update', $question->quiz);

        $validated = $request->validate([
            'media_type' => 'required|in:text,paragraph,image,video,audio,document,slide',
            'media_path' => 'nullable|string',
            'media_url' => 'nullable|url',
            'description' => 'nullable|string',
            'requires_completion' => 'boolean',
            'player_layout' => 'nullable|in:default,framed,cinema,compact',
            'player_caption' => 'nullable|string|max:255',
            'poster_file' => 'nullable|image|max:5120|mimes:jpg,jpeg,png,webp',
            'file' => 'nullable|file|max:51200',
        ]);

        $mediaType = $validated['media_type'];
        $mediaPath = $validated['media_path'] ?? null;
        $mediaUrl = $validated['media_url'] ?? null;
        $posterPath = null;

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $allowedExtensions = [
                'image' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'],
                'video' => ['mp4', 'webm', 'ogg', 'mov'],
                'audio' => ['mp3', 'wav', 'ogg', 'm4a'],
                'document' => ['pdf', 'doc', 'docx', 'ppt', 'pptx'],
                'slide' => ['pdf', 'ppt', 'pptx'],
            ];

            if (!array_key_exists($mediaType, $allowedExtensions)) {
                return response()->json([
                    'message' => 'File upload is only supported for image, video, audio, document, and slide media types.',
                ], 422);
            }

            $extension = strtolower($file->getClientOriginalExtension());
            if (!in_array($extension, $allowedExtensions[$mediaType], true)) {
                return response()->json([
                    'message' => 'Uploaded file type does not match selected media type.',
                ], 422);
            }

            $storedPath = $file->store('question-media', 'public');
            $mediaPath = Storage::url($storedPath);
        }

        if ($request->hasFile('poster_file')) {
            $posterStoredPath = $request->file('poster_file')->store('question-media-posters', 'public');
            $posterPath = Storage::url($posterStoredPath);
        }

        if (in_array($mediaType, ['image', 'video', 'audio', 'document', 'slide'], true) && !$mediaPath && !$mediaUrl) {
            return response()->json([
                'message' => 'Please provide an upload file, media URL, or media path for this media type.',
            ], 422);
        }

        if (in_array($mediaType, ['text', 'paragraph', 'slide'], true) && empty($validated['description'])) {
            return response()->json([
                'message' => 'Paragraph/slide media requires description/content text.',
            ], 422);
        }

        $maxOrder = QuestionMedia::where('question_id', $question->id)->max('order') ?? 0;

        $media = QuestionMedia::create([
            'question_id' => $question->id,
            'media_type' => $mediaType,
            'media_path' => $mediaPath,
            'media_url' => $mediaUrl,
            'description' => $validated['description'] ?? null,
            'requires_completion' => $validated['requires_completion'] ?? false,
            'player_layout' => $validated['player_layout'] ?? 'default',
            'player_caption' => $validated['player_caption'] ?? null,
            'poster_path' => $posterPath,
            'order' => $maxOrder + 1,
        ]);

        return response()->json($media, 201);
    }
}
