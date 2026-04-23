<?php

namespace Database\Seeders;

use App\Models\Translation;
use Illuminate\Database\Seeder;

class TranslationSeeder extends Seeder
{
    public function run(): void
    {
        // Build the full translation map then bulk-upsert.
        // Structure: [ group => [ key => [ locale => value ] ] ]
        $translations = $this->buildTranslations();

        $rows = [];
        $now  = now();

        foreach ($translations as $group => $keys) {
            foreach ($keys as $key => $locales) {
                foreach ($locales as $locale => $value) {
                    $rows[] = [
                        'locale'     => $locale,
                        'group'      => $group,
                        'key'        => $key,
                        'value'      => $value,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
        }

        // Upsert in chunks to avoid query size limits
        foreach (array_chunk($rows, 200) as $chunk) {
            Translation::upsert($chunk, ['locale', 'group', 'key'], ['value', 'updated_at']);
        }

        $this->command->info('✔ Translations seeded: ' . count($rows) . ' rows');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TRANSLATION DATA
    // ─────────────────────────────────────────────────────────────────────────

    private function buildTranslations(): array
    {
        return [

            // ═════════════════════════════════════════════════════════════════
            // COMMON — shared across all screens
            // ═════════════════════════════════════════════════════════════════
            'common' => [
                'app_name' => [
                    'en' => 'Sbrai Hub',
                    'fr' => 'Sbrai Hub',
                    'ha' => 'Sbrai Hub',
                    'ig' => 'Sbrai Hub',
                    'yo' => 'Sbrai Hub',
                ],
                'loading' => [
                    'en' => 'Loading...',
                    'fr' => 'Chargement...',
                    'ha' => 'Ana lodi...',
                    'ig' => 'Na-ebu...',
                    'yo' => 'Ngba...',
                ],
                'retry' => [
                    'en' => 'Retry',
                    'fr' => 'Réessayer',
                    'ha' => 'Sake gwadawa',
                    'ig' => 'Nwalee ọzọ',
                    'yo' => 'Gbiyanju lẹẹkansii',
                ],
                'save' => [
                    'en' => 'Save',
                    'fr' => 'Enregistrer',
                    'ha' => 'Ajiye',
                    'ig' => 'Chekwaa',
                    'yo' => 'Fipamọ',
                ],
                'cancel' => [
                    'en' => 'Cancel',
                    'fr' => 'Annuler',
                    'ha' => 'Soke',
                    'ig' => 'Kagbuo',
                    'yo' => 'Fagilee',
                ],
                'delete' => [
                    'en' => 'Delete',
                    'fr' => 'Supprimer',
                    'ha' => 'Goge',
                    'ig' => 'Hichapụ',
                    'yo' => 'Paarẹ',
                ],
                'confirm' => [
                    'en' => 'Confirm',
                    'fr' => 'Confirmer',
                    'ha' => 'Tabbatar',
                    'ig' => 'Kwenye',
                    'yo' => 'Jẹrisi',
                ],
                'back' => [
                    'en' => 'Back',
                    'fr' => 'Retour',
                    'ha' => 'Koma baya',
                    'ig' => 'Laghachi',
                    'yo' => 'Padà',
                ],
                'submit' => [
                    'en' => 'Submit',
                    'fr' => 'Soumettre',
                    'ha' => 'Aika',
                    'ig' => 'Nyefee',
                    'yo' => 'Firanṣẹ',
                ],
                'close' => [
                    'en' => 'Close',
                    'fr' => 'Fermer',
                    'ha' => 'Rufe',
                    'ig' => 'Mechie',
                    'yo' => 'Pa',
                ],
                'edit' => [
                    'en' => 'Edit',
                    'fr' => 'Modifier',
                    'ha' => 'Gyara',
                    'ig' => 'Dezie',
                    'yo' => 'Ṣatunṣe',
                ],
                'view_all' => [
                    'en' => 'View All',
                    'fr' => 'Voir tout',
                    'ha' => 'Duba duka',
                    'ig' => 'Hụ nke ọ bụla',
                    'yo' => 'Wo gbogbo',
                ],
                'no_results' => [
                    'en' => 'No items found.',
                    'fr' => 'Aucun article trouvé.',
                    'ha' => 'Ba a sami kaya ba.',
                    'ig' => 'Enweghị ihe a chọtara.',
                    'yo' => 'Ko nkan ti a rí.',
                ],
                'error_generic' => [
                    'en' => 'Something went wrong. Please try again.',
                    'fr' => 'Une erreur est survenue. Veuillez réessayer.',
                    'ha' => 'Wani abu ya kasa. Da fatan a sake gwadawa.',
                    'ig' => 'Ihe ọjọọ mere. Biko nwalee ọzọ.',
                    'yo' => 'Nkan kan lọ aṣiṣe. Jọwọ gbiyanju lẹẹkansii.',
                ],
                'network_error' => [
                    'en' => 'No internet connection.',
                    'fr' => 'Pas de connexion Internet.',
                    'ha' => 'Babu haɗin intanet.',
                    'ig' => 'Enweghị njikọ ịntanetị.',
                    'yo' => 'Ko asopọ intanẹẹti.',
                ],
                'success' => [
                    'en' => 'Success',
                    'fr' => 'Succès',
                    'ha' => 'Nasara',
                    'ig' => 'Ihe ọ dị mma',
                    'yo' => 'Aṣeyọri',
                ],
                'required_field' => [
                    'en' => 'This field is required.',
                    'fr' => 'Ce champ est obligatoire.',
                    'ha' => 'Ana buƙatar wannan filin.',
                    'ig' => 'A chọrọ oghere a.',
                    'yo' => 'Aaye yii nilo.',
                ],
                'items_count' => [
                    'en' => '{count} items',
                    'fr' => '{count} articles',
                    'ha' => 'Kayan {count}',
                    'ig' => 'Ihe {count}',
                    'yo' => 'Nkan {count}',
                ],
                'call' => [
                    'en' => 'Call',
                    'fr' => 'Appeler',
                    'ha' => 'Kira',
                    'ig' => 'Kpọọ',
                    'yo' => 'Pe',
                ],
                'chat' => [
                    'en' => 'Chat',
                    'fr' => 'Discuter',
                    'ha' => 'Tattaunawa',
                    'ig' => 'Kparịta',
                    'yo' => 'Ibaraẹnisọ',
                ],
                'vendor' => [
                    'en' => 'Vendor',
                    'fr' => 'Vendeur',
                    'ha' => 'Dan kasuwa',
                    'ig' => 'Onye na-ere ahịa',
                    'yo' => 'Olutaja',
                ],
                'buyer' => [
                    'en' => 'Buyer',
                    'fr' => 'Acheteur',
                    'ha' => 'Mai siya',
                    'ig' => 'Onye na-azụ ahịa',
                    'yo' => 'Olura',
                ],
                'not_provided' => [
                    'en' => 'Not provided',
                    'fr' => 'Non fourni',
                    'ha' => 'Ba a bayar ba',
                    'ig' => 'Anaghị enye',
                    'yo' => 'Ko pese',
                ],
                'version' => [
                    'en' => 'Sbrai Hub v1.0.0',
                    'fr' => 'Sbrai Hub v1.0.0',
                    'ha' => 'Sbrai Hub v1.0.0',
                    'ig' => 'Sbrai Hub v1.0.0',
                    'yo' => 'Sbrai Hub v1.0.0',
                ],
            ],

            // ═════════════════════════════════════════════════════════════════
            // HOME SCREEN (VendorHomeScreen)
            // ═════════════════════════════════════════════════════════════════
            'home' => [
                'search_header' => [
                    'en' => 'What Are You Looking For?',
                    'fr' => 'Que cherchez-vous?',
                    'ha' => 'Mene ne Kake Nema?',
                    'ig' => 'Gịnị Ka I Na-achọ?',
                    'yo' => 'Kini O Ń Wá?',
                ],
                'search_placeholder' => [
                    'en' => 'I am looking for...',
                    'fr' => 'Je cherche...',
                    'ha' => 'Ina neman...',
                    'ig' => 'Achọrọ m...',
                    'yo' => 'Mo ń wá...',
                ],
                'location_all' => [
                    'en' => 'All Nigeria',
                    'fr' => 'Tout le Nigeria',
                    'ha' => 'Duk Najeriya',
                    'ig' => 'Naịjirịa nile',
                    'yo' => 'Gbogbo Naijiria',
                ],
                'recommended' => [
                    'en' => 'Recommended for You',
                    'fr' => 'Recommandé pour vous',
                    'ha' => 'An ba da shawara a gare ku',
                    'ig' => 'Akwadoro unu',
                    'yo' => 'A ṣeduro fún Ọ',
                ],
                'results_for' => [
                    'en' => 'Results for {category}',
                    'fr' => 'Résultats pour {category}',
                    'ha' => 'Sakamako na {category}',
                    'ig' => 'Nsonaazụ maka {category}',
                    'yo' => 'Abajade fún {category}',
                ],
                'trending' => [
                    'en' => 'Trending',
                    'fr' => 'Tendances',
                    'ha' => 'Mai shahara',
                    'ig' => 'Na-aga n\'ihu',
                    'yo' => 'Gbigbona',
                ],
                'post_ad' => [
                    'en' => 'Post Ad',
                    'fr' => 'Publier une annonce',
                    'ha' => 'Aika Labari',
                    'ig' => 'Tinye Mgbasa Ozi',
                    'yo' => 'Fún Ipolongo',
                ],
                'filter_by_state' => [
                    'en' => 'Filter by State',
                    'fr' => 'Filtrer par État',
                    'ha' => 'Tace ta Jiha',
                    'ig' => 'Chọpụta site na Steeti',
                    'yo' => 'Ṣẹ àlẹmọ nipasẹ Ipinlẹ',
                ],
                'added_to_favorites' => [
                    'en' => '{name} added to favorites',
                    'fr' => '{name} ajouté aux favoris',
                    'ha' => 'An ƙara {name} zuwa abubuwan da aka fi so',
                    'ig' => 'Agbakwunyere {name} na ndị a hụrụ n\'anya',
                    'yo' => '{name} ni afikun si awọn ayanfẹ',
                ],
            ],

            // ═════════════════════════════════════════════════════════════════
            // CATEGORIES
            // ═════════════════════════════════════════════════════════════════
            'categories' => [
                'sharp_sand' => [
                    'en' => 'Sharp Sand',
                    'fr' => 'Sable grossier',
                    'ha' => 'Yashi mai kaifi',
                    'ig' => 'Ọcha ọchá',
                    'yo' => 'Iyanrin gígùn',
                ],
                'granite' => [
                    'en' => 'Granite',
                    'fr' => 'Granit',
                    'ha' => 'Dutsen girke',
                    'ig' => 'Nkume granite',
                    'yo' => 'Granite',
                ],
                'blocks' => [
                    'en' => 'Blocks',
                    'fr' => 'Blocs',
                    'ha' => 'Tubalan gini',
                    'ig' => 'Blocks',
                    'yo' => 'Awọn Biriki',
                ],
                'cement' => [
                    'en' => 'Cement',
                    'fr' => 'Ciment',
                    'ha' => 'Siminti',
                    'ig' => 'Sementi',
                    'yo' => 'Simenti',
                ],
                'iron_rods' => [
                    'en' => 'Iron Rods',
                    'fr' => 'Barres de fer',
                    'ha' => 'Sandunan ƙarfe',
                    'ig' => 'Igwe ígwè',
                    'yo' => 'Ọpá Irin',
                ],
                'paints' => [
                    'en' => 'Paints',
                    'fr' => 'Peintures',
                    'ha' => 'Fenti',
                    'ig' => 'Peenti',
                    'yo' => 'Àwọ̀ Irun',
                ],
                'furniture' => [
                    'en' => 'Furniture',
                    'fr' => 'Mobilier',
                    'ha' => 'Kayan gida',
                    'ig' => 'Ihe ndọchi ụlọ',
                    'yo' => 'Ìtàgé ilé',
                ],
                'scaffolding' => [
                    'en' => 'Scaffolding',
                    'fr' => 'Échafaudage',
                    'ha' => 'Tsarin ginin wucin gadi',
                    'ig' => 'Ihe njikọ ụlọ',
                    'yo' => 'Ètò Ìkọ́lé',
                ],
                'logistics' => [
                    'en' => 'Logistics',
                    'fr' => 'Logistique',
                    'ha' => 'Jigilar kaya',
                    'ig' => 'Nbuso ihe',
                    'yo' => 'Gbigbe Ẹrù',
                ],
                'borehole' => [
                    'en' => 'Borehole',
                    'fr' => 'Forage',
                    'ha' => 'Rijiyar burtsatse',
                    'ig' => 'Iyi ọ̀gbọ̀',
                    'yo' => 'Kanga',
                ],
                'cleaning' => [
                    'en' => 'Cleaning',
                    'fr' => 'Nettoyage',
                    'ha' => 'Tsaftacewa',
                    'ig' => 'Ichebe',
                    'yo' => 'Ìmọ́tótó',
                ],
                'fumigation' => [
                    'en' => 'Fumigation',
                    'fr' => 'Fumigation',
                    'ha' => 'Fesa magungunan ƙwari',
                    'ig' => 'Igbu ụmụ ahụhụ',
                    'yo' => 'Fifa egbogi kokoro',
                ],
                'apartments' => [
                    'en' => 'Apartments',
                    'fr' => 'Appartements',
                    'ha' => 'Gidajen haya',
                    'ig' => 'Ụlọ ikike',
                    'yo' => 'Àgọ ilé',
                ],
                'houses' => [
                    'en' => 'Houses',
                    'fr' => 'Maisons',
                    'ha' => 'Gidaje',
                    'ig' => 'Ụlọ',
                    'yo' => 'Ilé',
                ],
                'commercial' => [
                    'en' => 'Commercial',
                    'fr' => 'Commercial',
                    'ha' => 'Kasuwanci',
                    'ig' => 'Azụmahịa',
                    'yo' => 'Iṣowo',
                ],
                'land' => [
                    'en' => 'Land',
                    'fr' => 'Terrain',
                    'ha' => 'Filaye',
                    'ig' => 'Ala',
                    'yo' => 'Ilẹ',
                ],
            ],

            // ═════════════════════════════════════════════════════════════════
            // AUTH SCREENS
            // ═════════════════════════════════════════════════════════════════
            'auth' => [
                'create_account' => [
                    'en' => 'Create Your Account',
                    'fr' => 'Créez votre compte',
                    'ha' => 'Ƙirƙiri Asusun ku',
                    'ig' => 'Mepụta Akaụntụ Gị',
                    'yo' => 'Ṣẹda Ìkọsílẹ Rẹ',
                ],
                'choose_account_type' => [
                    'en' => 'Choose how you want to use Store Hub',
                    'fr' => 'Choisissez comment utiliser Store Hub',
                    'ha' => 'Zaɓi yadda kake son amfani da Store Hub',
                    'ig' => 'Họrọ otu ị chọrọ iji Store Hub',
                    'yo' => 'Yan bí o ṣe fẹ lo Store Hub',
                ],
                'sign_up_buyer' => [
                    'en' => 'Sign Up as Buyer',
                    'fr' => 'S\'inscrire en tant qu\'acheteur',
                    'ha' => 'Yi rajista a matsayin mai siya',
                    'ig' => 'Debanye aha dị ka onye na-azụ',
                    'yo' => 'Forukọsilẹ gẹgẹ bi Olura',
                ],
                'sign_up_vendor' => [
                    'en' => 'Sign Up as Vendor',
                    'fr' => 'S\'inscrire en tant que vendeur',
                    'ha' => 'Yi rajista a matsayin ɗan kasuwa',
                    'ig' => 'Debanye aha dị ka onye na-ere',
                    'yo' => 'Forukọsilẹ gẹgẹ bi Olutaja',
                ],
                'sign_in' => [
                    'en' => 'Sign In',
                    'fr' => 'Se connecter',
                    'ha' => 'Shiga',
                    'ig' => 'Banye',
                    'yo' => 'Wọle',
                ],
                'sign_out' => [
                    'en' => 'Sign Out',
                    'fr' => 'Se déconnecter',
                    'ha' => 'Fita',
                    'ig' => 'Pụọ',
                    'yo' => 'Jade',
                ],
                'email' => [
                    'en' => 'Email',
                    'fr' => 'Email',
                    'ha' => 'Imel',
                    'ig' => 'Imeelụ',
                    'yo' => 'Ìméèlì',
                ],
                'password' => [
                    'en' => 'Password',
                    'fr' => 'Mot de passe',
                    'ha' => 'Kalmar sirri',
                    'ig' => 'Paswọọdụ',
                    'yo' => 'Ọrọ Aṣina',
                ],
                'confirm_password' => [
                    'en' => 'Confirm Password',
                    'fr' => 'Confirmer le mot de passe',
                    'ha' => 'Tabbatar kalmar sirri',
                    'ig' => 'Kwenye Paswọọdụ',
                    'yo' => 'Jẹrisi Ọrọ Aṣina',
                ],
                'full_name' => [
                    'en' => 'Full Name',
                    'fr' => 'Nom complet',
                    'ha' => 'Cikakken suna',
                    'ig' => 'Aha ọ bụla',
                    'yo' => 'Orúkọ Pípé',
                ],
                'phone_number' => [
                    'en' => 'Phone Number',
                    'fr' => 'Numéro de téléphone',
                    'ha' => 'Lambar waya',
                    'ig' => 'Nọmba ekwentị',
                    'yo' => 'Nọmba Fóònù',
                ],
                'business_name' => [
                    'en' => 'Business Name',
                    'fr' => 'Nom de l\'entreprise',
                    'ha' => 'Sunan kasuwanci',
                    'ig' => 'Aha azụmahịa',
                    'yo' => 'Orúkọ Iṣowo',
                ],
                'business_address' => [
                    'en' => 'Business Address',
                    'fr' => 'Adresse de l\'entreprise',
                    'ha' => 'Adireshin kasuwanci',
                    'ig' => 'Adreesị azụmahịa',
                    'yo' => 'Àdírẹ́sì Iṣowo',
                ],
                'state' => [
                    'en' => 'State',
                    'fr' => 'État',
                    'ha' => 'Jiha',
                    'ig' => 'Steeti',
                    'yo' => 'Ìpínlẹ̀',
                ],
                'city' => [
                    'en' => 'City',
                    'fr' => 'Ville',
                    'ha' => 'Birni',
                    'ig' => 'Obodo',
                    'yo' => 'Ìlú',
                ],
                'have_buyer_account' => [
                    'en' => 'Have a buyer account?',
                    'fr' => 'Vous avez un compte acheteur?',
                    'ha' => 'Kuna da asusun mai siya?',
                    'ig' => 'Ị nwere akaụntụ onye azụ?',
                    'yo' => 'Ní ìkọsílẹ olura?',
                ],
                'have_vendor_account' => [
                    'en' => 'Have a vendor account?',
                    'fr' => 'Vous avez un compte vendeur?',
                    'ha' => 'Kuna da asusun ɗan kasuwa?',
                    'ig' => 'Ị nwere akaụntụ onye na-ere?',
                    'yo' => 'Ní ìkọsílẹ olutaja?',
                ],
                'forgot_password' => [
                    'en' => 'Forgot Password?',
                    'fr' => 'Mot de passe oublié?',
                    'ha' => 'Manta kalmar sirri?',
                    'ig' => 'Chefuo Paswọọdụ?',
                    'yo' => 'Gbàgbé Ọrọ Aṣina?',
                ],
                'change_password' => [
                    'en' => 'Change Password',
                    'fr' => 'Changer le mot de passe',
                    'ha' => 'Canza kalmar sirri',
                    'ig' => 'Gbanwee Paswọọdụ',
                    'yo' => 'Yípadà Ọrọ Aṣina',
                ],
                'current_password' => [
                    'en' => 'Current Password',
                    'fr' => 'Mot de passe actuel',
                    'ha' => 'Kalmar sirri ta yanzu',
                    'ig' => 'Paswọọdụ ugbu a',
                    'yo' => 'Ọrọ Aṣina Lọwọlọwọ',
                ],
                'new_password' => [
                    'en' => 'New Password',
                    'fr' => 'Nouveau mot de passe',
                    'ha' => 'Sabuwar kalmar sirri',
                    'ig' => 'Paswọọdụ ọhụrụ',
                    'yo' => 'Ọrọ Aṣina Tuntun',
                ],
                'update_password' => [
                    'en' => 'Update Password',
                    'fr' => 'Mettre à jour le mot de passe',
                    'ha' => 'Sabunta kalmar sirri',
                    'ig' => 'Melite Paswọọdụ',
                    'yo' => 'Ṣe Imudojuiwọn Ọrọ Aṣina',
                ],
                'password_changed' => [
                    'en' => 'Password changed successfully. Please log in again.',
                    'fr' => 'Mot de passe changé avec succès.',
                    'ha' => 'An canza kalmar sirri. Da fatan a sake shiga.',
                    'ig' => 'Agbanweela paswọọdụ. Biko banye ọzọ.',
                    'yo' => 'Ọrọ aṣina yípadà. Jọwọ wọle lẹẹkansii.',
                ],
                'terms_agreement' => [
                    'en' => 'By continuing, you agree to Store Hub\'s Terms of Service and Privacy Policy',
                    'fr' => 'En continuant, vous acceptez les Conditions d\'utilisation et la Politique de confidentialité',
                    'ha' => 'Ta hanyar ci gaba, kuna yarda da Sharuɗɗan Amfani da Manufofin Sirri',
                    'ig' => 'Site n\'aga n\'ihu, ị kwenye na Usoro Ọrụ na Amụma Nzuzo',
                    'yo' => 'Nípa tẹsiwaju, o gba ẹ̀tọ̀ Àwọn òfin Iṣẹ́ àti Ìmúlò Àṣírí',
                ],
                'logout' => [
                    'en' => 'Logout',
                    'fr' => 'Déconnexion',
                    'ha' => 'Fita',
                    'ig' => 'Pụọ',
                    'yo' => 'Jade',
                ],
                'delete_account' => [
                    'en' => 'Delete Account',
                    'fr' => 'Supprimer le compte',
                    'ha' => 'Goge asusu',
                    'ig' => 'Hichapụ Akaụntụ',
                    'yo' => 'Paarẹ Ìkọsílẹ',
                ],
                'delete_confirm_title' => [
                    'en' => 'Are you absolutely sure?',
                    'fr' => 'Êtes-vous absolument sûr?',
                    'ha' => 'Shin kun tabbata?',
                    'ig' => 'Ị dị n\'otu n\'otu n\'ụzọ niile?',
                    'yo' => 'Ṣé o dájú pátápátá?',
                ],
                'delete_confirm_body' => [
                    'en' => 'This action cannot be undone. Enter your password to confirm.',
                    'fr' => 'Cette action est irréversible. Entrez votre mot de passe pour confirmer.',
                    'ha' => 'Ba za a iya mayar da wannan ba. Shigar da kalmar sirri don tabbatarwa.',
                    'ig' => 'Enweghi ike megharịa ọrụ a. Tinye paswọọdụ gị iji kwenye.',
                    'yo' => 'Iṣe yii ko le ṣe. Tẹ ọrọ aṣina rẹ lati jẹrisi.',
                ],
            ],

            // ═════════════════════════════════════════════════════════════════
            // ADS / POST AD SCREEN
            // ═════════════════════════════════════════════════════════════════
            'ads' => [
                'post_ad_title' => [
                    'en' => 'Post an Ad',
                    'fr' => 'Publier une annonce',
                    'ha' => 'Aika Labari',
                    'ig' => 'Tụpụ mgbasa ozi',
                    'yo' => 'Ṣe Ipolongo',
                ],
                'step_of' => [
                    'en' => 'Step {current} of {total}',
                    'fr' => 'Étape {current} sur {total}',
                    'ha' => 'Mataki {current} na {total}',
                    'ig' => 'Nzọụkwụ {current} nke {total}',
                    'yo' => 'Igbésẹ {current} nínú {total}',
                ],
                'select_category' => [
                    'en' => 'Select Category',
                    'fr' => 'Sélectionner une catégorie',
                    'ha' => 'Zaɓi Nau\'in',
                    'ig' => 'Họrọ Udi',
                    'yo' => 'Yan Ẹka',
                ],
                'listing_type' => [
                    'en' => 'Listing Type',
                    'fr' => 'Type d\'annonce',
                    'ha' => 'Nau\'in Jerin',
                    'ig' => 'Ụdị Ndepụta',
                    'yo' => 'Irú Ìtòlẹsẹẹ',
                ],
                'product' => [
                    'en' => 'Product',
                    'fr' => 'Produit',
                    'ha' => 'Kaya',
                    'ig' => 'Ngwaahịa',
                    'yo' => 'Ọja',
                ],
                'service' => [
                    'en' => 'Service',
                    'fr' => 'Service',
                    'ha' => 'Sabis',
                    'ig' => 'Ọrụ',
                    'yo' => 'Iṣẹ',
                ],
                'property' => [
                    'en' => 'Property',
                    'fr' => 'Propriété',
                    'ha' => 'Dukiya',
                    'ig' => 'Ọrụ onwe',
                    'yo' => 'Ohun Ini',
                ],
                'for_rent' => [
                    'en' => 'For Rent',
                    'fr' => 'À louer',
                    'ha' => 'Don hayar',
                    'ig' => 'Maka mgbazinye',
                    'yo' => 'Fún Yà',
                ],
                'for_sale' => [
                    'en' => 'For Sale',
                    'fr' => 'À vendre',
                    'ha' => 'Don sayarwa',
                    'ig' => 'Maka ire',
                    'yo' => 'Fún Tita',
                ],
                'upload_photos' => [
                    'en' => 'Upload Photos',
                    'fr' => 'Télécharger des photos',
                    'ha' => 'Loda Hotuna',
                    'ig' => 'Bulite Foto',
                    'yo' => 'Gbé Àwọn Fọ́tò Sórí',
                ],
                'add_up_to_photos' => [
                    'en' => 'Add up to 5 photos',
                    'fr' => 'Ajouter jusqu\'à 5 photos',
                    'ha' => 'Ƙara hotuna har zuwa 5',
                    'ig' => 'Tinye foto ruo 5',
                    'yo' => 'Ṣàfikún fọ́tò títí dé 5',
                ],
                'listing_details' => [
                    'en' => 'Listing Details',
                    'fr' => 'Détails de l\'annonce',
                    'ha' => 'Cikakkun Bayanai na Jerin',
                    'ig' => 'Nkọwa ndepụta',
                    'yo' => 'Àwọn Àlàyé Ìtòlẹsẹẹ',
                ],
                'ad_title' => [
                    'en' => 'Ad Title',
                    'fr' => 'Titre de l\'annonce',
                    'ha' => 'Taken Labari',
                    'ig' => 'Aha mgbasa ozi',
                    'yo' => 'Àkọlé Ipolongo',
                ],
                'description' => [
                    'en' => 'Description',
                    'fr' => 'Description',
                    'ha' => 'Bayani',
                    'ig' => 'Nkọwa',
                    'yo' => 'Àpejúwe',
                ],
                'price' => [
                    'en' => 'Price (₦)',
                    'fr' => 'Prix (₦)',
                    'ha' => 'Farashi (₦)',
                    'ig' => 'Ọnụ ahịa (₦)',
                    'yo' => 'Iye (₦)',
                ],
                'price_unit' => [
                    'en' => 'Unit',
                    'fr' => 'Unité',
                    'ha' => 'Naúni',
                    'ig' => 'Otu',
                    'yo' => 'Ẹyọkan',
                ],
                'location' => [
                    'en' => 'Location',
                    'fr' => 'Localisation',
                    'ha' => 'Wuri',
                    'ig' => 'Ọnọdụ',
                    'yo' => 'Ibi',
                ],
                'bedrooms' => [
                    'en' => 'Bedrooms',
                    'fr' => 'Chambres',
                    'ha' => 'Ɗakunan kwana',
                    'ig' => 'Ụlọ ụra',
                    'yo' => 'Yàrá Ibùsùn',
                ],
                'size_sqft' => [
                    'en' => 'Size (Sqft)',
                    'fr' => 'Superficie (pi²)',
                    'ha' => 'Girman (Sqft)',
                    'ig' => 'Nha (Sqft)',
                    'yo' => 'Ìwọ̀n (Sqft)',
                ],
                'publish_ad' => [
                    'en' => 'Publish Ad Now',
                    'fr' => 'Publier l\'annonce maintenant',
                    'ha' => 'Buga Labari Yanzu',
                    'ig' => 'Biputa Mgbasa Ozi Ugbu a',
                    'yo' => 'Ṣàgbéjáde Ipolongo Bayi',
                ],
                'next_upload_media' => [
                    'en' => 'Next: Upload Media',
                    'fr' => 'Suivant: Télécharger des médias',
                    'ha' => 'Na gaba: Loda Media',
                    'ig' => 'Ọzọ: Bulite Mgbasa',
                    'yo' => 'Tókàn: Gbé Media Sórí',
                ],
                'next_details' => [
                    'en' => 'Next: Details',
                    'fr' => 'Suivant: Détails',
                    'ha' => 'Na gaba: Bayani',
                    'ig' => 'Ọzọ: Nkọwa',
                    'yo' => 'Tókàn: Àwọn Àlàyé',
                ],
                'ad_published' => [
                    'en' => 'Ad Published Successfully!',
                    'fr' => 'Annonce publiée avec succès!',
                    'ha' => 'An buga labari cikin nasara!',
                    'ig' => 'Ejipụtala mgbasa ozi nke ọma!',
                    'yo' => 'A ti ṣàgbéjáde Ipolongo!',
                ],
                'active' => [
                    'en' => 'Active',
                    'fr' => 'Actif',
                    'ha' => 'Mai aiki',
                    'ig' => 'Na-arụ ọrụ',
                    'yo' => 'Lọwọlọwọ',
                ],
                'inactive' => [
                    'en' => 'Inactive',
                    'fr' => 'Inactif',
                    'ha' => 'Ba mai aiki ba',
                    'ig' => 'Anaghị arụ ọrụ',
                    'yo' => 'Aisiṣẹ',
                ],
            ],

            // ═════════════════════════════════════════════════════════════════
            // CHAT SCREEN
            // ═════════════════════════════════════════════════════════════════
            'chat' => [
                'title' => [
                    'en' => 'Messages',
                    'fr' => 'Messages',
                    'ha' => 'Saƙonni',
                    'ig' => 'Ozi',
                    'yo' => 'Àwọn Ìfiranṣẹ',
                ],
                'type_message' => [
                    'en' => 'Type a message...',
                    'fr' => 'Écrivez un message...',
                    'ha' => 'Rubuta saƙo...',
                    'ig' => 'Dee ozi...',
                    'yo' => 'Tẹ ìfiranṣẹ...',
                ],
                'send' => [
                    'en' => 'Send',
                    'fr' => 'Envoyer',
                    'ha' => 'Aika',
                    'ig' => 'Zipu',
                    'yo' => 'Firanṣẹ',
                ],
                'no_messages' => [
                    'en' => 'No messages yet. Start a conversation!',
                    'fr' => 'Pas encore de messages. Commencez une conversation!',
                    'ha' => 'Babu saƙonni tukuna. Fara tattaunawa!',
                    'ig' => 'Enweghị ozi ọ bụla. Bido mkparịta ụka!',
                    'yo' => 'Ko ìfiranṣẹ kankan. Bẹ̀rẹ̀ ìbáraẹnisọ!',
                ],
                'is_available' => [
                    'en' => 'Hi, is this still available?',
                    'fr' => 'Bonjour, est-ce encore disponible?',
                    'ha' => 'Sannu, shin har yanzu akwai wannan?',
                    'ig' => 'Nnọọ, ọ dị ugbu a?',
                    'yo' => 'Báwo, ṣé ó ṣì wà?',
                ],
                'unread' => [
                    'en' => 'Unread',
                    'fr' => 'Non lu',
                    'ha' => 'Ba a karanta ba',
                    'ig' => 'Agụghị',
                    'yo' => 'A kò ka',
                ],
            ],

            // ═════════════════════════════════════════════════════════════════
            // VENDOR DASHBOARD
            // ═════════════════════════════════════════════════════════════════
            'dashboard' => [
                'title' => [
                    'en' => 'Vendor Dashboard',
                    'fr' => 'Tableau de bord Vendeur',
                    'ha' => 'Allon Ɗan Kasuwa',
                    'ig' => 'Ọdọ Onye na-ere',
                    'yo' => 'Pánẹ́lì Olutaja',
                ],
                'active_listings' => [
                    'en' => 'Active Listings',
                    'fr' => 'Annonces actives',
                    'ha' => 'Jerin Ayyuka',
                    'ig' => 'Ndepụta na-arụ ọrụ',
                    'yo' => 'Àwọn Ìtòlẹsẹẹ Lọwọlọwọ',
                ],
                'total_views' => [
                    'en' => 'Total Views',
                    'fr' => 'Vues totales',
                    'ha' => 'Jimlar Kallace-kallace',
                    'ig' => 'Ngụkọ nile',
                    'yo' => 'Àpapọ̀ Àwọn Ìwò',
                ],
                'messages' => [
                    'en' => 'Messages',
                    'fr' => 'Messages',
                    'ha' => 'Saƙonni',
                    'ig' => 'Ozi',
                    'yo' => 'Àwọn Ìfiranṣẹ',
                ],
                'total_sales' => [
                    'en' => 'Total Sales',
                    'fr' => 'Ventes totales',
                    'ha' => 'Jimlar Sayarwa',
                    'ig' => 'Ngụkọ ire nile',
                    'yo' => 'Àpapọ̀ Àwọn Títà',
                ],
                'overview' => [
                    'en' => 'Overview',
                    'fr' => 'Aperçu',
                    'ha' => 'Takaitawa',
                    'ig' => 'Nchịkọta',
                    'yo' => 'Àkópọ̀',
                ],
                'my_listings' => [
                    'en' => 'My Listings',
                    'fr' => 'Mes annonces',
                    'ha' => 'Jerin na',
                    'ig' => 'Ndepụta m',
                    'yo' => 'Àwọn Ìtòlẹsẹẹ Mi',
                ],
                'analytics' => [
                    'en' => 'Analytics',
                    'fr' => 'Analytiques',
                    'ha' => 'Nazari',
                    'ig' => 'Nyocha',
                    'yo' => 'Ìmọ̀-ìṣirò',
                ],
                'recent_activity' => [
                    'en' => 'Recent Activity',
                    'fr' => 'Activité récente',
                    'ha' => 'Ayyukan Kwanan Nan',
                    'ig' => 'Ọrụ n\'oge na-adịbeghị anya',
                    'yo' => 'Ìgbésẹ Àìpẹ́',
                ],
                'quick_actions' => [
                    'en' => 'Quick Actions',
                    'fr' => 'Actions rapides',
                    'ha' => 'Ayyukan Gaggawa',
                    'ig' => 'Ọrụ ngwa ngwa',
                    'yo' => 'Àwọn Iṣẹ́ Kíákíá',
                ],
                'post_new_ad' => [
                    'en' => 'Post New Ad',
                    'fr' => 'Publier une nouvelle annonce',
                    'ha' => 'Aika Sabon Labari',
                    'ig' => 'Tinye Mgbasa Ozi Ọhụrụ',
                    'yo' => 'Ṣe Ipolongo Tuntun',
                ],
                'view_messages' => [
                    'en' => 'View Messages',
                    'fr' => 'Voir les messages',
                    'ha' => 'Duba Saƙonni',
                    'ig' => 'Lee ozi',
                    'yo' => 'Wo Àwọn Ìfiranṣẹ',
                ],
                'manage_listings' => [
                    'en' => 'Manage Listings',
                    'fr' => 'Gérer les annonces',
                    'ha' => 'Sarrafa Jerin',
                    'ig' => 'Njikwa ndepụta',
                    'yo' => 'Ṣàkóso Àwọn Ìtòlẹsẹẹ',
                ],
                'ad_voucher' => [
                    'en' => 'Ad Voucher',
                    'fr' => 'Bon d\'annonce',
                    'ha' => 'Takaddun Labari',
                    'ig' => 'Mbinye mgbasa ozi',
                    'yo' => 'Fáwọ̀ Ipolongo',
                ],
                'available_balance' => [
                    'en' => 'Available Balance',
                    'fr' => 'Solde disponible',
                    'ha' => 'Ma\'aunin da ke akwai',
                    'ig' => 'Ego dị n\'aka',
                    'yo' => 'Iye Ọwọ Tó Wà',
                ],
                'use_for_promotions' => [
                    'en' => 'Use for ad promotions',
                    'fr' => 'Utiliser pour les promotions',
                    'ha' => 'Amfani don tallan labari',
                    'ig' => 'Jiri maka ngagharị mgbasa ozi',
                    'yo' => 'Lo fún àwọn ìpolongo',
                ],
                'performance_overview' => [
                    'en' => 'Performance Overview',
                    'fr' => 'Vue d\'ensemble des performances',
                    'ha' => 'Duba Aikin Gaba Ɗaya',
                    'ig' => 'Nchịkọta arụmọrụ',
                    'yo' => 'Àkópọ̀ Ìṣẹ́',
                ],
                'response_rate' => [
                    'en' => 'Response Rate',
                    'fr' => 'Taux de réponse',
                    'ha' => 'Ƙimar amsa',
                    'ig' => 'Ọnụ ọgụgụ nzaghachi',
                    'yo' => 'Ìpèsè Ìdáhùn',
                ],
                'top_performing' => [
                    'en' => 'Top Performing Listings',
                    'fr' => 'Meilleures annonces',
                    'ha' => 'Jerin Mafi Aiki',
                    'ig' => 'Ndepụta ndị kacha arụ ọrụ',
                    'yo' => 'Àwọn Ìtòlẹsẹẹ Tó Ń Ṣẹ́ Jùlọ',
                ],
                'no_listings_yet' => [
                    'en' => 'You haven\'t uploaded any products yet.',
                    'fr' => 'Vous n\'avez pas encore téléchargé de produits.',
                    'ha' => 'Ba ka aika kayan kai tukuna ba.',
                    'ig' => 'Ị emebeghị ngwaahịa ọ bụla ka ugbu a.',
                    'yo' => 'O kò tíì gbé ọja kankan sórí.',
                ],
            ],

            // ═════════════════════════════════════════════════════════════════
            // PROFILE SCREEN
            // ═════════════════════════════════════════════════════════════════
            'profile' => [
                'title' => [
                    'en' => 'My Profile',
                    'fr' => 'Mon profil',
                    'ha' => 'Bayanan na',
                    'ig' => 'Profaịlụ m',
                    'yo' => 'Profáílì Mi',
                ],
                'account_information' => [
                    'en' => 'Account Information',
                    'fr' => 'Informations du compte',
                    'ha' => 'Bayanan Asusu',
                    'ig' => 'Ozi akaụntụ',
                    'yo' => 'Àlàyé Ìkọsílẹ',
                ],
                'your_statistics' => [
                    'en' => 'Your Statistics',
                    'fr' => 'Vos statistiques',
                    'ha' => 'Ƙididdiga ku',
                    'ig' => 'Ọnụ ọgụgụ gị',
                    'yo' => 'Àwọn Ìṣirò Rẹ',
                ],
                'kyc_information' => [
                    'en' => 'KYC Information',
                    'fr' => 'Informations KYC',
                    'ha' => 'Bayanan KYC',
                    'ig' => 'Ozi KYC',
                    'yo' => 'Àlàyé KYC',
                ],
                'verified' => [
                    'en' => 'Verified',
                    'fr' => 'Vérifié',
                    'ha' => 'An tabbatar',
                    'ig' => 'Akwadoro',
                    'yo' => 'Ìjẹ́rìísí',
                ],
                'not_verified' => [
                    'en' => 'Not Verified',
                    'fr' => 'Non vérifié',
                    'ha' => 'Ba a tabbatar ba',
                    'ig' => 'Akwadoghị',
                    'yo' => 'Kò jẹ́rìísí',
                ],
                'joined' => [
                    'en' => 'Joined {date}',
                    'fr' => 'Rejoint {date}',
                    'ha' => 'Ya shiga {date}',
                    'ig' => 'Sonyere {date}',
                    'yo' => 'Darapọ̀ mọ̀ {date}',
                ],
                'save_changes' => [
                    'en' => 'Save Changes',
                    'fr' => 'Enregistrer les modifications',
                    'ha' => 'Ajiye Canje-canje',
                    'ig' => 'Chekwaa Mgbanwe',
                    'yo' => 'Fi Àwọn Àyípadà Pamọ́',
                ],
                'profile_updated' => [
                    'en' => 'Profile updated successfully!',
                    'fr' => 'Profil mis à jour avec succès!',
                    'ha' => 'An sabunta bayanan cikin nasara!',
                    'ig' => 'Emelitere profaịlụ nke ọma!',
                    'yo' => 'A ti ṣe imudojuiwọn profáílì!',
                ],
            ],

            // ═════════════════════════════════════════════════════════════════
            // SETTINGS SCREEN
            // ═════════════════════════════════════════════════════════════════
            'settings' => [
                'title' => [
                    'en' => 'Settings',
                    'fr' => 'Paramètres',
                    'ha' => 'Saituna',
                    'ig' => 'Ntọala',
                    'yo' => 'Ìtòsílẹ',
                ],
                'notifications' => [
                    'en' => 'Notifications',
                    'fr' => 'Notifications',
                    'ha' => 'Sanarwa',
                    'ig' => 'Ọkwa',
                    'yo' => 'Àwọn Ìkílokoìló',
                ],
                'notifications_desc' => [
                    'en' => 'Manage your notification preferences',
                    'fr' => 'Gérez vos préférences de notification',
                    'ha' => 'Sarrafa zaɓuɓɓukan sanarwarka',
                    'ig' => 'Jikwaa mmasị ọkwa gị',
                    'yo' => 'Ṣàkóso àwọn ààyò ìkílokoìló rẹ',
                ],
                'new_listings' => [
                    'en' => 'New Listings',
                    'fr' => 'Nouvelles annonces',
                    'ha' => 'Sabon Jeri',
                    'ig' => 'Ndepụta ọhụrụ',
                    'yo' => 'Àwọn Ìtòlẹsẹẹ Tuntun',
                ],
                'new_listings_desc' => [
                    'en' => 'Get notified about new items in your area',
                    'fr' => 'Soyez notifié des nouveaux articles dans votre région',
                    'ha' => 'Samun sanarwa game da sabon kaya a yankinku',
                    'ig' => 'Nweta ọkwa maka ihe ọhụrụ n\'ebe i nọ',
                    'yo' => 'Gba ìkílokoìló nípa àwọn nkan tuntun ní àgbègbè rẹ',
                ],
                'price_drops' => [
                    'en' => 'Price Drops',
                    'fr' => 'Baisses de prix',
                    'ha' => 'Raguwar Farashi',
                    'ig' => 'Ọdịda ọnụ ahịa',
                    'yo' => 'Ìṣẹ́lẹ Iye',
                ],
                'price_drops_desc' => [
                    'en' => 'Alert me when prices drop on favorited items',
                    'fr' => 'Alertez-moi quand les prix baissent sur les favoris',
                    'ha' => 'Faɗa mini lokacin da farashin abubuwan da aka fi so ya sauka',
                    'ig' => 'Dọọ m aka mgbe ọnụ ahịa dada n\'ihe a hụrụ n\'anya',
                    'yo' => 'Jẹ kí n mọ bí iye bá ṣẹ̀ lórí àwọn nkan ayanfẹ',
                ],
                'messages_notif' => [
                    'en' => 'Messages',
                    'fr' => 'Messages',
                    'ha' => 'Saƙonni',
                    'ig' => 'Ozi',
                    'yo' => 'Àwọn Ìfiranṣẹ',
                ],
                'messages_notif_desc' => [
                    'en' => 'Receive notifications for new messages',
                    'fr' => 'Recevoir des notifications pour les nouveaux messages',
                    'ha' => 'Karɓi sanarwa don sabon saƙonni',
                    'ig' => 'Nata ọkwa maka ozi ọhụrụ',
                    'yo' => 'Gba àwọn ìkílokoìló fún àwọn ìfiranṣẹ tuntun',
                ],
                'promotions' => [
                    'en' => 'Promotions',
                    'fr' => 'Promotions',
                    'ha' => 'Tallace-tallace',
                    'ig' => 'Ndozi',
                    'yo' => 'Àwọn Ìgbéga',
                ],
                'promotions_desc' => [
                    'en' => 'Receive promotional offers and deals',
                    'fr' => 'Recevoir des offres promotionnelles',
                    'ha' => 'Karɓi tayin tallace-tallace',
                    'ig' => 'Nata onyinye na nkwekọrịta ndozi',
                    'yo' => 'Gba àwọn ìfunni àti àwọn ìdúúró',
                ],
                'privacy_security' => [
                    'en' => 'Privacy & Security',
                    'fr' => 'Confidentialité et sécurité',
                    'ha' => 'Sirri & Tsaro',
                    'ig' => 'Nzuzo & Nchekwa',
                    'yo' => 'Àṣírì & Àbò',
                ],
                'privacy_security_desc' => [
                    'en' => 'Control your privacy and account security',
                    'fr' => 'Contrôlez votre confidentialité et la sécurité de votre compte',
                    'ha' => 'Sarrafa sirrin ku da tsaron asusu',
                    'ig' => 'Chịkwaa nzuzo gị na nchekwa akaụntụ',
                    'yo' => 'Ṣàkóso àṣírì àti ààbò ìkọsílẹ rẹ',
                ],
                'show_online_status' => [
                    'en' => 'Show Online Status',
                    'fr' => 'Afficher le statut en ligne',
                    'ha' => 'Nuna Matsayin Online',
                    'ig' => 'Gosipụta Ọnọdụ Ịntanetị',
                    'yo' => 'Fi Ìpele Ìlànà hàn',
                ],
                'show_online_status_desc' => [
                    'en' => 'Let others see when you\'re online',
                    'fr' => 'Permettre aux autres de voir quand vous êtes en ligne',
                    'ha' => 'Bari wasu su ga lokacin da kuke online',
                    'ig' => 'Kwe ka ndị ọzọ hụ mgbe ị nọ n\'ịntanetị',
                    'yo' => 'Jẹ kí àwọn mìíràn rí nígbà tí o wà lórí Íńtánẹ́ẹ̀tì',
                ],
                'show_phone_number' => [
                    'en' => 'Show Phone Number',
                    'fr' => 'Afficher le numéro de téléphone',
                    'ha' => 'Nuna Lambar Waya',
                    'ig' => 'Gosipụta Nọmba ekwentị',
                    'yo' => 'Fi Nọmba Fóònù hàn',
                ],
                'show_phone_number_desc' => [
                    'en' => 'Display phone number on profile',
                    'fr' => 'Afficher le numéro de téléphone sur le profil',
                    'ha' => 'Nuna lambar waya a bayanan',
                    'ig' => 'Gosipụta nọmba ekwentị n\'profaịlụ',
                    'yo' => 'Fi nọmba fóònù hàn lórí profáílì',
                ],
                'allow_messages' => [
                    'en' => 'Allow Messages',
                    'fr' => 'Autoriser les messages',
                    'ha' => 'Yarda da Saƙonni',
                    'ig' => 'Kwe ikefee ozi',
                    'yo' => 'Gbà Àwọn Ìfiranṣẹ Láàyè',
                ],
                'allow_messages_desc' => [
                    'en' => 'Allow users to send you messages',
                    'fr' => 'Permettre aux utilisateurs de vous envoyer des messages',
                    'ha' => 'Bari masu amfani su aiko maka saƙonni',
                    'ig' => 'Kwe ka ndị ọzọ ziga gị ozi',
                    'yo' => 'Gbà àwọn olùmúlò láàyè láti fi ìfiranṣẹ ránṣẹ́ sí ọ',
                ],
                'language_region' => [
                    'en' => 'Language & Region',
                    'fr' => 'Langue et région',
                    'ha' => 'Harshe & Yanki',
                    'ig' => 'Asụsụ & Mpaghara',
                    'yo' => 'Èdè & Ẹkùn',
                ],
                'language' => [
                    'en' => 'Language',
                    'fr' => 'Langue',
                    'ha' => 'Harshe',
                    'ig' => 'Asụsụ',
                    'yo' => 'Èdè',
                ],
                'currency' => [
                    'en' => 'Currency',
                    'fr' => 'Devise',
                    'ha' => 'Kuɗi',
                    'ig' => 'Ego',
                    'yo' => 'Owó',
                ],
                'terms_conditions' => [
                    'en' => 'Terms & Conditions',
                    'fr' => 'Termes et conditions',
                    'ha' => 'Sharuɗɗa da Yanayi',
                    'ig' => 'Usoro na Ọnọdụ',
                    'yo' => 'Àwọn Òfin àti Ìpèsè',
                ],
                'privacy_policy' => [
                    'en' => 'Privacy Policy',
                    'fr' => 'Politique de confidentialité',
                    'ha' => 'Manufofin Sirri',
                    'ig' => 'Amụma Nzuzo',
                    'yo' => 'Ìlànà Àṣírì',
                ],
                'help_support' => [
                    'en' => 'Help & Support',
                    'fr' => 'Aide et support',
                    'ha' => 'Taimako & Goyon Baya',
                    'ig' => 'Enyemaka & Nkwado',
                    'yo' => 'Ìrànwọ́ & Àtìlẹyìn',
                ],
                'danger_zone' => [
                    'en' => 'Danger Zone',
                    'fr' => 'Zone de danger',
                    'ha' => 'Yankin Hadari',
                    'ig' => 'Mpaghara Nsogbu',
                    'yo' => 'Àgbègbè Ewu',
                ],
                'danger_zone_desc' => [
                    'en' => 'Irreversible and destructive actions',
                    'fr' => 'Actions irréversibles et destructrices',
                    'ha' => 'Ayyukan da ba za a iya mayarwa ba',
                    'ig' => 'Ọrụ ndị enweghị ike ịtụgharị',
                    'yo' => 'Àwọn iṣẹ́ tí kò ṣeépadà',
                ],
                'settings_saved' => [
                    'en' => 'Settings saved.',
                    'fr' => 'Paramètres enregistrés.',
                    'ha' => 'An ajiye saituna.',
                    'ig' => 'Echekwara ntọala.',
                    'yo' => 'A ti fi àwọn ìtòsílẹ pamọ́.',
                ],
            ],

            // ═════════════════════════════════════════════════════════════════
            // VOUCHER / WALLET
            // ═════════════════════════════════════════════════════════════════
            'voucher' => [
                'title' => [
                    'en' => 'My Wallet',
                    'fr' => 'Mon portefeuille',
                    'ha' => 'Jakar na',
                    'ig' => 'Akpa m',
                    'yo' => 'Wọ́lẹ́tì Mi',
                ],
                'top_up' => [
                    'en' => 'Top Up',
                    'fr' => 'Recharger',
                    'ha' => 'Cika',
                    'ig' => 'Tọpụta',
                    'yo' => 'Fikun',
                ],
                'boost_ad' => [
                    'en' => 'Boost Ad',
                    'fr' => 'Booster l\'annonce',
                    'ha' => 'Ƙara Labari',
                    'ig' => 'Kwalite Mgbasa Ozi',
                    'yo' => 'Àgbéga Ipolongo',
                ],
                'transaction_history' => [
                    'en' => 'Transaction History',
                    'fr' => 'Historique des transactions',
                    'ha' => 'Tarihin Ma\'amala',
                    'ig' => 'Akụkọ ihe mere eme azụmahịa',
                    'yo' => 'Ìtàn Ìdúnnú',
                ],
                'credit' => [
                    'en' => 'Credit',
                    'fr' => 'Crédit',
                    'ha' => 'Kuɗin shiga',
                    'ig' => 'Kredit',
                    'yo' => 'Ìfitẹ̀',
                ],
                'debit' => [
                    'en' => 'Debit',
                    'fr' => 'Débit',
                    'ha' => 'Kuɗin fita',
                    'ig' => 'Debit',
                    'yo' => 'Ìgbéfọwọ́',
                ],
                'standard_boost' => [
                    'en' => 'Standard (₦500 / 7 days)',
                    'fr' => 'Standard (₦500 / 7 jours)',
                    'ha' => 'Na yau da kullun (₦500 / kwana 7)',
                    'ig' => 'Ọkọlọtọ (₦500 / ụbọchị 7)',
                    'yo' => 'Ìpìlẹ̀ (₦500 / ọjọ́ 7)',
                ],
                'featured_boost' => [
                    'en' => 'Featured (₦1,500 / 7 days)',
                    'fr' => 'En vedette (₦1 500 / 7 jours)',
                    'ha' => 'Na musamman (₦1,500 / kwana 7)',
                    'ig' => 'Pụtara ìhè (₦1,500 / ụbọchị 7)',
                    'yo' => 'Àgbékalẹ̀ (₦1,500 / ọjọ́ 7)',
                ],
                'premium_boost' => [
                    'en' => 'Premium (₦3,000 / 7 days)',
                    'fr' => 'Premium (₦3 000 / 7 jours)',
                    'ha' => 'Firimiyam (₦3,000 / kwana 7)',
                    'ig' => 'Premium (₦3,000 / ụbọchị 7)',
                    'yo' => 'Àgbéjáde Gíga (₦3,000 / ọjọ́ 7)',
                ],
                'insufficient_balance' => [
                    'en' => 'Insufficient voucher balance.',
                    'fr' => 'Solde de bon insuffisant.',
                    'ha' => 'Ma\'aunin takaddun bai isa ba.',
                    'ig' => 'Ego dị na mbinye ezughi oke.',
                    'yo' => 'Iye wọ́lẹ́tì kò tó.',
                ],
            ],
        ];
    }
}
