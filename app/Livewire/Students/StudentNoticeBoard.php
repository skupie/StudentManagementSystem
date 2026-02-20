<?php

namespace App\Livewire\Students;

use App\Models\StudentNotice;
use DOMDocument;
use DOMElement;
use Livewire\Component;
use Livewire\WithPagination;

class StudentNoticeBoard extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = 'all';

    public array $form = [
        'title' => '',
        'body' => '',
        'notice_date' => '',
        'is_active' => true,
    ];

    public ?int $editingId = null;
    public ?int $confirmingDeleteId = null;
    public string $confirmingDeleteTitle = '';

    protected function rules(): array
    {
        return [
            'form.title' => ['required', 'string', 'max:255'],
            'form.body' => ['required', 'string'],
            'form.notice_date' => ['required', 'date'],
            'form.is_active' => ['boolean'],
        ];
    }

    public function render()
    {
        $baseQuery = StudentNotice::query()
            ->with('creator')
            ->withCount([
                'acknowledgements as acknowledged_count' => function ($query) {
                    $query->where('action', 'acknowledged');
                },
            ])
            ->when($this->search !== '', function ($query) {
                $query->where(function ($inner) {
                    $inner->where('title', 'like', '%' . $this->search . '%')
                        ->orWhere('body', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->statusFilter === 'active', fn ($query) => $query->where('is_active', true))
            ->when($this->statusFilter === 'inactive', fn ($query) => $query->where('is_active', false));

        $notices = (clone $baseQuery)
            ->orderByDesc('notice_date')
            ->orderByDesc('id')
            ->paginate(10);

        return view('livewire.students.student-notice-board', [
            'notices' => $notices,
            'totalCount' => StudentNotice::count(),
            'activeCount' => StudentNotice::where('is_active', true)->count(),
            'todayCount' => StudentNotice::whereDate('notice_date', now()->toDateString())->count(),
        ]);
    }

    public function save(): void
    {
        $this->validate();

        StudentNotice::updateOrCreate(
            ['id' => $this->editingId],
            [
                'title' => trim((string) $this->form['title']),
                'body' => $this->sanitizeNoticeBody((string) $this->form['body']),
                'notice_date' => $this->form['notice_date'],
                'is_active' => (bool) $this->form['is_active'],
                'created_by' => $this->editingId ? StudentNotice::whereKey($this->editingId)->value('created_by') : auth()->id(),
                'updated_by' => auth()->id(),
            ]
        );

        $this->dispatch('notify', message: $this->editingId ? 'Notice updated.' : 'Notice created.');
        $this->resetForm();
    }

    public function edit(int $noticeId): void
    {
        $notice = StudentNotice::findOrFail($noticeId);

        $this->editingId = $notice->id;
        $this->form = [
            'title' => $notice->title,
            'body' => $notice->body,
            'notice_date' => optional($notice->notice_date)->format('Y-m-d'),
            'is_active' => (bool) $notice->is_active,
        ];
    }

    public function promptDelete(int $noticeId): void
    {
        $notice = StudentNotice::find($noticeId);
        if (! $notice) {
            return;
        }

        $this->confirmingDeleteId = $notice->id;
        $this->confirmingDeleteTitle = $notice->title;
    }

    public function cancelDelete(): void
    {
        $this->confirmingDeleteId = null;
        $this->confirmingDeleteTitle = '';
    }

    public function delete(int $noticeId): void
    {
        $notice = StudentNotice::find($noticeId);
        if ($notice) {
            $notice->delete();
        }

        $this->cancelDelete();
        $this->dispatch('notify', message: 'Notice deleted.');
    }

    public function resetForm(): void
    {
        $this->editingId = null;
        $this->form = [
            'title' => '',
            'body' => '',
            'notice_date' => now()->toDateString(),
            'is_active' => true,
        ];
    }

    public function mount(): void
    {
        if (! $this->form['notice_date']) {
            $this->form['notice_date'] = now()->toDateString();
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function toggleActive(int $noticeId): void
    {
        $notice = StudentNotice::find($noticeId);
        if (! $notice) {
            return;
        }

        $notice->update([
            'is_active' => ! $notice->is_active,
            'updated_by' => auth()->id(),
        ]);

        $this->dispatch('notify', message: $notice->is_active ? 'Notice activated.' : 'Notice deactivated.');
    }

    protected function sanitizeNoticeBody(string $body): string
    {
        $body = trim($body);
        if ($body === '') {
            return '';
        }

        // Keep line breaks when plain text is entered from textarea.
        $body = str_replace(["\r\n", "\r", "\n"], '<br>', $body);

        $doc = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8"><div>' . $body . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $allowedTags = ['div', 'p', 'br', 'strong', 'b', 'em', 'i', 'u', 'span', 'ul', 'ol', 'li'];
        $allowedStyledTags = ['div', 'p', 'span'];

        $nodes = $doc->getElementsByTagName('*');
        for ($i = $nodes->length - 1; $i >= 0; $i--) {
            $node = $nodes->item($i);
            if (! $node instanceof DOMElement) {
                continue;
            }

            $tag = strtolower($node->tagName);
            if (! in_array($tag, $allowedTags, true)) {
                $text = $doc->createTextNode($node->textContent ?? '');
                $node->parentNode?->replaceChild($text, $node);
                continue;
            }

            $attrs = [];
            foreach ($node->attributes ?? [] as $attr) {
                $attrs[] = $attr->name;
            }

            foreach ($attrs as $attrName) {
                if ($attrName !== 'style') {
                    $node->removeAttribute($attrName);
                    continue;
                }

                if (! in_array($tag, $allowedStyledTags, true)) {
                    $node->removeAttribute('style');
                    continue;
                }

                $style = (string) $node->getAttribute('style');
                $safeStyle = $this->extractSafeFontSizeStyle($style);
                if ($safeStyle === '') {
                    $node->removeAttribute('style');
                } else {
                    $node->setAttribute('style', $safeStyle);
                }
            }
        }

        $wrapper = $doc->getElementsByTagName('div')->item(0);
        if (! $wrapper instanceof DOMElement) {
            return '';
        }

        $html = '';
        foreach ($wrapper->childNodes as $child) {
            $html .= $doc->saveHTML($child);
        }

        return trim($html);
    }

    protected function extractSafeFontSizeStyle(string $style): string
    {
        if (! preg_match('/font-size\s*:\s*(\d{1,2})px/i', $style, $matches)) {
            return '';
        }

        $size = (int) ($matches[1] ?? 0);
        if ($size < 10 || $size > 40) {
            return '';
        }

        return 'font-size: ' . $size . 'px;';
    }
}
