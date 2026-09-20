<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReviewResource\Pages;
use App\Models\Review;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Nothing reaches a product page until it is approved here — new reviews land
 * in "Awaiting approval" and the navigation badge counts them.
 */
class ReviewResource extends Resource
{
    protected static ?string $model = Review::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedStar;

    protected static ?int $navigationSort = 7;

    public static function getNavigationBadge(): ?string
    {
        $n = Review::where('is_approved', false)->count();

        return $n > 0 ? (string) $n : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Review')->columns(2)->schema([
                Select::make('product_id')->relationship('product', 'name')->searchable()->preload()->required()
                    ->disabledOn('edit'),
                Select::make('rating')->options([5 => '5 — Excellent', 4 => '4 — Good', 3 => '3 — Okay', 2 => '2 — Poor', 1 => '1 — Bad'])
                    ->required(),
                TextInput::make('name')->label('Customer name')->required()->maxLength(80),
                TextInput::make('email')->email()->maxLength(190)->placeholder('—'),
                TextInput::make('title')->maxLength(120)->columnSpanFull(),
                Textarea::make('body')->label('Review')->required()->rows(5)->columnSpanFull()
                    ->helperText('You may tidy typos, but keep the customer\'s meaning.'),
            ]),
            Section::make('Publishing')->columns(2)->schema([
                Toggle::make('is_approved')->label('Approved (shows on the product page)')
                    ->helperText('Off until you have read it.'),
                Toggle::make('is_verified_purchase')->label('Verified purchase')
                    ->helperText('Set automatically when the reviewer\'s account has bought this product.'),
                Textarea::make('admin_reply')->label('Reply from Kayaa')->rows(3)->columnSpanFull()
                    ->helperText('Shown under the review once the review is approved. Leave empty for none.'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                IconColumn::make('is_approved')->label('')->boolean()
                    ->trueIcon(Heroicon::CheckCircle)->falseIcon(Heroicon::OutlinedClock)
                    ->trueColor('success')->falseColor('warning'),
                TextColumn::make('created_at')->dateTime('d M, H:i')->sortable(),
                TextColumn::make('product.name')->searchable()->limit(28),
                TextColumn::make('rating')->formatStateUsing(fn (int $state) => str_repeat('★', $state).str_repeat('☆', 5 - $state))->sortable(),
                TextColumn::make('name')->label('Customer')->searchable()
                    ->description(fn (Review $r) => $r->is_verified_purchase ? 'Verified purchase' : null),
                TextColumn::make('body')->label('Review')->limit(60)->wrap(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Filter::make('pending')->label('Awaiting approval')->default()
                    ->query(fn (Builder $query) => $query->where('is_approved', false)),
                SelectFilter::make('rating')->options([5 => '5', 4 => '4', 3 => '3', 2 => '2', 1 => '1']),
                SelectFilter::make('product')->relationship('product', 'name')->searchable()->preload(),
            ])
            ->recordActions([
                Action::make('approve')->icon(Heroicon::Check)->color('success')
                    ->visible(fn (Review $r) => ! $r->is_approved)
                    ->action(fn (Review $r) => $r->approve()),
                Action::make('unapprove')->label('Hide')->icon(Heroicon::EyeSlash)->color('gray')
                    ->visible(fn (Review $r) => $r->is_approved)
                    ->requiresConfirmation()
                    ->action(fn (Review $r) => $r->unapprove()),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkAction::make('approve')->label('Approve selected')->icon(Heroicon::Check)->color('success')
                    ->action(fn (Collection $records) => $records->each->approve())
                    ->deselectRecordsAfterCompletion(),
                DeleteBulkAction::make(),
            ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReviews::route('/'),
            'edit' => Pages\EditReview::route('/{record}/edit'),
        ];
    }
}
