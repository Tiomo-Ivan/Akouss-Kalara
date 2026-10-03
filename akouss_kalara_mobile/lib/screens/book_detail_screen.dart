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
                color: Color(0xFFC0392B),
              ),
              const SizedBox(height: 12),
              const Text(
                'Impossible de charger le livre.',
                textAlign: TextAlign.center,
                style: TextStyle(
                  color: Color(0xFF1A1A2E),
                  fontSize: 18,
                  fontWeight: FontWeight.bold,
                ),
              ),
              const SizedBox(height: 8),
              Text(
                _errorMessage!,
                textAlign: TextAlign.center,
                style: const TextStyle(
                  color: Color(0xFF6B7280),
                ),
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

    return RefreshIndicator(
      onRefresh: _loadBookDetail,
      child: SingleChildScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.fromLTRB(20, 20, 20, 30),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            _buildBookHeader(detail),
            const SizedBox(height: 30),
            const Text(
              'Offres disponibles',
              style: TextStyle(
                color: Color(0xFF1A1A2E),
                fontSize: 22,
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 12),
            _buildOffers(),
          ],
        ),
      ),
    );
  }

  Widget _buildBookHeader(Map<String, dynamic> detail) {
    final title = detail['titre']?.toString() ?? widget.book.title;
    final author = detail['auteur']?.toString() ?? widget.book.author;
    final category =
        detail['categorie_nom']?.toString() ?? widget.book.category;
    final description = detail['description']?.toString();

    final image = detail['image_couverture']?.toString() ??
        widget.book.coverImage;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _buildCover(image),
        const SizedBox(height: 20),
        Text(
          title,
          style: const TextStyle(
            color: Color(0xFF1A1A2E),
            fontSize: 28,
            fontWeight: FontWeight.bold,
          ),
        ),
        const SizedBox(height: 8),
        Text(
          author,
          style: const TextStyle(
            color: Color(0xFF6B7280),
            fontSize: 17,
          ),
        ),
        const SizedBox(height: 12),
        Container(
          padding: const EdgeInsets.symmetric(
            horizontal: 10,
            vertical: 6,
          ),
          decoration: BoxDecoration(
            color: const Color(0xFFEAF0FF),
            borderRadius: BorderRadius.circular(20),
          ),
          child: Text(
            category,
            style: const TextStyle(
              color: Color(0xFF2255CC),
              fontSize: 12,
              fontWeight: FontWeight.w600,
            ),
          ),
        ),
        if (description != null && description.isNotEmpty) ...[
          const SizedBox(height: 20),
          const Text(
            'Description',
            style: TextStyle(
              color: Color(0xFF1A1A2E),
              fontSize: 18,
              fontWeight: FontWeight.bold,
            ),
          ),
          const SizedBox(height: 8),
          Text(
            description,
            style: const TextStyle(
              color: Color(0xFF4B5563),
              fontSize: 15,
              height: 1.5,
            ),
          ),
        ],
      ],
    );
  }

  Widget _buildCover(String? imageUrl) {
    return AspectRatio(
      aspectRatio: 3 / 4,
      child: Container(
        width: double.infinity,
        decoration: BoxDecoration(
          color: const Color(0xFFF0F2F5),
          borderRadius: BorderRadius.circular(10),
          border: Border.all(
            color: const Color(0xFFE5E7EB),
          ),
        ),
        clipBehavior: Clip.antiAlias,
        child: imageUrl != null && imageUrl.isNotEmpty
            ? Image.network(
                imageUrl,
                fit: BoxFit.cover,
                errorBuilder: (
                  context,
                  error,
                  stackTrace,
                ) {
                  return _buildCoverPlaceholder();
                },
              )
            : _buildCoverPlaceholder(),
      ),
    );
  }

  Widget _buildCoverPlaceholder() {
    return const Center(
      child: Icon(
        Icons.menu_book,
        size: 90,
        color: Color(0xFF2255CC),
      ),
    );
  }

  Widget _buildOffers() {
    if (_offers.isEmpty) {
      return Card(
        child: Padding(
          padding: const EdgeInsets.all(20),
          child: Row(
            children: const [
              Icon(
                Icons.info_outline,
                color: Color(0xFF6B7280),
              ),
              SizedBox(width: 12),
              Expanded(
                child: Text(
                  'Aucune offre disponible pour ce livre.',
                  style: TextStyle(
                    color: Color(0xFF4B5563),
                  ),
                ),
              ),
            ],
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
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Container(
                  width: 42,
                  height: 42,
                  decoration: BoxDecoration(
                    color: const Color(0xFFEAF0FF),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: const Icon(
                    Icons.store_outlined,
                    color: Color(0xFF2255CC),
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        offer.vendeurNom,
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(
                          color: Color(0xFF1A1A2E),
                          fontSize: 16,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        offer.isPhysical
                            ? 'Livre physique'
                            : 'Livre numérique',
                        style: const TextStyle(
                          color: Color(0xFF6B7280),
                          fontSize: 13,
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(width: 8),
                Text(
                  offer.formattedPrice,
                  style: const TextStyle(
                    color: Color(0xFF16213E),
                    fontSize: 18,
                    fontWeight: FontWeight.w800,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 14),
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: [
                _buildInfoBadge(
                  icon: offer.isPhysical
                      ? Icons.inventory_2_outlined
                      : Icons.download_outlined,
                  label: offer.isPhysical
                      ? 'Physique'
                      : 'Numérique',
                ),
                if (offer.isPhysical)
                  _buildInfoBadge(
                    icon: Icons.inventory_outlined,
                    label:
                        'Stock : ${offer.quantiteStock ?? 0}',
                  ),
                if (offer.etatArticle != null &&
                    offer.etatArticle!.isNotEmpty)
                  _buildInfoBadge(
                    icon: Icons.check_circle_outline,
                    label: 'État : ${offer.etatArticle}',
                  ),
              ],
            ),
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
                    : const Icon(
                        Icons.add_shopping_cart,
                      ),
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

  Widget _buildInfoBadge({
    required IconData icon,
    required String label,
  }) {
    return Container(
      padding: const EdgeInsets.symmetric(
        horizontal: 9,
        vertical: 6,
      ),
      decoration: BoxDecoration(
        color: const Color(0xFFF7F8FA),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(
          color: const Color(0xFFE5E7EB),
        ),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(
            icon,
            size: 15,
            color: const Color(0xFF6B7280),
          ),
          const SizedBox(width: 5),
          Text(
            label,
            style: const TextStyle(
              color: Color(0xFF4B5563),
              fontSize: 12,
              fontWeight: FontWeight.w600,
            ),
          ),
        ],
      ),
    );
  }
}