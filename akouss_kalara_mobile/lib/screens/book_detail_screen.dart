import 'package:flutter/material.dart';

import '../models/book.dart';
import '../models/book_offer.dart';
import '../services/api_service.dart';
import '../services/auth_service.dart';
import 'login_screen.dart';

class BookDetailScreen extends StatefulWidget {
final Book book;

const BookDetailScreen({
super.key,
required this.book,
});

@override
State<BookDetailScreen> createState() => _BookDetailScreenState();
}

class _BookDetailScreenState extends State<BookDetailScreen> {
Map<String, dynamic>? _bookDetail;
List<BookOffer> _offers = [];

bool _isLoading = true;
String? _errorMessage;
int? _addingOfferId;

@override
void initState() {
super.initState();
_loadBookDetail();
}

Future<void> _loadBookDetail() async {
setState(() {
_isLoading = true;
_errorMessage = null;
});

try {
  final data = await ApiService.getBookDetail(widget.book.id);

  final offersData = data['offres'];

  final offers = offersData is List
      ? offersData
          .map(
            (item) => BookOffer.fromJson(
              Map<String, dynamic>.from(item),
            ),
          )
          .toList()
      : <BookOffer>[];

  if (!mounted) return;

  setState(() {
    _bookDetail = data;
    _offers = offers;
    _isLoading = false;
  });
} catch (e) {
  if (!mounted) return;

  setState(() {
    _errorMessage = e.toString();
    _isLoading = false;
  });
}

}

Future<void> _addToCart(BookOffer offer) async {
final token = await AuthService.getToken();

if (!mounted) return;

if (token == null || token.isEmpty) {
  final result = await Navigator.push(
    context,
    MaterialPageRoute(
      builder: (context) => const LoginScreen(),
    ),
  );

  if (result != true) {
    return;
  }

  final newToken = await AuthService.getToken();

  if (newToken == null || newToken.isEmpty) {
    if (!mounted) return;

    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(
        content: Text(
          'Veuillez vous connecter pour ajouter un article au panier.',
        ),
      ),
    );

    return;
  }

  await _sendAddToCart(
    offer: offer,
    token: newToken,
  );

  return;
}

await _sendAddToCart(
  offer: offer,
  token: token,
);

}

Future<void> _sendAddToCart({
required BookOffer offer,
required String token,
}) async {
setState(() {
_addingOfferId = offer.id;
});

try {
  final result = await ApiService.addToCart(
    token: token,
    offerId: offer.id,
    quantity: 1,
  );

  if (!mounted) return;

  final message = result['message']?.toString();

  ScaffoldMessenger.of(context).showSnackBar(
    SnackBar(
      content: Text(
        message?.isNotEmpty == true
            ? message!
            : 'Livre ajouté au panier.',
      ),
    ),
  );
} catch (e) {
  if (!mounted) return;

  ScaffoldMessenger.of(context).showSnackBar(
    SnackBar(
      content: Text(
        e.toString().replaceFirst('Exception: ', ''),
      ),
    ),
  );
} finally {
  if (mounted) {
    setState(() {
      _addingOfferId = null;
    });
  }
}

}

@override
Widget build(BuildContext context) {
return Scaffold(
appBar: AppBar(
title: const Text('Détail du livre'),
),
body: _buildContent(),
);
}

Widget _buildContent() {
if (_isLoading) {
return const Center(
child: CircularProgressIndicator(),
);
}

if (_errorMessage != null) {
  return Center(
    child: Padding(
      padding: const EdgeInsets.all(20),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          const Icon(
            Icons.error_outline,
            size: 48,
          ),
          const SizedBox(height: 12),
          const Text(
            'Impossible de charger le livre.',
            textAlign: TextAlign.center,
            style: TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.bold,
            ),
          ),
          const SizedBox(height: 8),
          Text(
            _errorMessage!,
            textAlign: TextAlign.center,
          ),
          const SizedBox(height: 16),
          ElevatedButton.icon(
            onPressed: _loadBookDetail,
            icon: const Icon(Icons.refresh),
            label: const Text('Réessayer'),
          ),
        ],
      ),
    ),
  );
}

final detail = _bookDetail!;

return SingleChildScrollView(
  padding: const EdgeInsets.all(20),
  child: Column(
    crossAxisAlignment: CrossAxisAlignment.start,
    children: [
      _buildBookHeader(detail),
      const SizedBox(height: 28),
      const Text(
        'Offres disponibles',
        style: TextStyle(
          fontSize: 22,
          fontWeight: FontWeight.bold,
        ),
      ),
      const SizedBox(height: 12),
      _buildOffers(),
    ],
  ),
);

}

Widget _buildBookHeader(Map<String, dynamic> detail) {
final title = detail['titre']?.toString() ?? widget.book.title;
final author = detail['auteur']?.toString() ?? widget.book.author;
final category =
detail['categorie_nom']?.toString() ?? widget.book.category;
final description = detail['description']?.toString();

return Column(
  crossAxisAlignment: CrossAxisAlignment.start,
  children: [
    Container(
      height: 260,
      width: double.infinity,
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.primaryContainer,
        borderRadius: BorderRadius.circular(16),
      ),
      child: Icon(
        Icons.menu_book,
        size: 100,
        color: Theme.of(context).colorScheme.onPrimaryContainer,
      ),
    ),
    const SizedBox(height: 20),
    Text(
      title,
      style: const TextStyle(
        fontSize: 28,
        fontWeight: FontWeight.bold,
      ),
    ),
    const SizedBox(height: 8),
    Text(
      author,
      style: TextStyle(
        fontSize: 17,
        color: Colors.grey.shade700,
      ),
    ),
    const SizedBox(height: 12),
    Chip(
      label: Text(category),
    ),
    if (description != null && description.isNotEmpty) ...[
      const SizedBox(height: 16),
      Text(
        description,
        style: const TextStyle(
          fontSize: 16,
          height: 1.5,
        ),
      ),
    ],
  ],
);

}

Widget _buildOffers() {
if (_offers.isEmpty) {
return const Card(
child: Padding(
padding: EdgeInsets.all(20),
child: Text(
'Aucune offre disponible pour ce livre.',
),
),
);
}

return Column(
  children: _offers.map(_buildOfferCard).toList(),
);

}

Widget _buildOfferCard(BookOffer offer) {
final isAdding = _addingOfferId == offer.id;

return Card(
  margin: const EdgeInsets.only(bottom: 12),
  child: Padding(
    padding: const EdgeInsets.all(16),
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            const Icon(Icons.store_outlined),
            const SizedBox(width: 10),
            Expanded(
              child: Text(
                offer.vendeurNom,
                style: const TextStyle(
                  fontSize: 17,
                  fontWeight: FontWeight.bold,
                ),
              ),
            ),
            Text(
              offer.formattedPrice,
              style: TextStyle(
                fontSize: 18,
                fontWeight: FontWeight.bold,
                color: Theme.of(context).colorScheme.primary,
              ),
            ),
          ],
        ),
        const SizedBox(height: 12),
        Row(
          children: [
            Icon(
              offer.isPhysical
                  ? Icons.inventory_2_outlined
                  : Icons.download_outlined,
              size: 20,
            ),
            const SizedBox(width: 8),
            Text(
              offer.isPhysical
                  ? 'Livre physique'
                  : 'Livre numérique',
            ),
          ],
        ),
        if (offer.isPhysical) ...[
          const SizedBox(height: 8),
          Text(
            'Stock disponible : ${offer.quantiteStock ?? 0}',
          ),
          if (offer.etatArticle != null) ...[
            const SizedBox(height: 4),
            Text(
              'État : ${offer.etatArticle}',
            ),
          ],
        ],
        const SizedBox(height: 16),
        SizedBox(
          width: double.infinity,
          child: ElevatedButton.icon(
            onPressed: isAdding
                ? null
                : () => _addToCart(offer),
            icon: isAdding
                ? const SizedBox(
                    width: 20,
                    height: 20,
                    child: CircularProgressIndicator(
                      strokeWidth: 2,
                    ),
                  )
                : const Icon(Icons.add_shopping_cart),
            label: Text(
              isAdding
                  ? 'Ajout en cours...'
                  : 'Ajouter au panier',
            ),
          ),
        ),
      ],
    ),
  ),
);

}
}