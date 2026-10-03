import 'package:flutter/material.dart';

import '../models/book.dart';
import '../services/api_service.dart';
import '../widgets/book_card.dart';
import 'book_detail_screen.dart';

class CatalogScreen extends StatefulWidget {
  const CatalogScreen({super.key});

  @override
  State<CatalogScreen> createState() => _CatalogScreenState();
}

class _CatalogScreenState extends State<CatalogScreen> {
  List<Book> _books = [];
  List<Map<String, dynamic>> _categories = [];

  bool _isLoading = true;
  bool _isLoadingCategories = true;
  String? _errorMessage;

  String? _selectedCategoryName;

  final TextEditingController _searchController =
      TextEditingController();

  @override
  void initState() {
    super.initState();
    _loadCategories();
    _loadBooks();
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  Future<void> _loadCategories() async {
    try {
      final data = await ApiService.getCategories();

      if (!mounted) return;

      setState(() {
        _categories = data
            .map(
              (item) => Map<String, dynamic>.from(item),
            )
            .toList();
        _isLoadingCategories = false;
      });
    } catch (_) {
      if (!mounted) return;

      setState(() {
        _isLoadingCategories = false;
      });
    }
  }

  Future<void> _loadBooks() async {
  setState(() {
    _isLoading = true;
    _errorMessage = null;
  });

  try {
    final data = await ApiService.getBooks(
      query: _searchController.text.trim(),
    );

    var books = data
        .map(
          (item) => Book.fromJson(
            Map<String, dynamic>.from(item),
          ),
        )
        .toList();

    if (_selectedCategoryName != null) {
      books = books.where((book) {
        return book.category == _selectedCategoryName;
      }).toList();
    }

    if (!mounted) return;

    setState(() {
      _books = books;
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

  void _selectCategory(String? categoryName) {
  setState(() {
    _selectedCategoryName = categoryName;
  });

  _loadBooks();
}

  Future<void> _refresh() async {
    await Future.wait([
      _loadBooks(),
      _loadCategories(),
    ]);
  }

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Padding(
            padding: EdgeInsets.fromLTRB(20, 20, 20, 8),
            child: Text(
              'Catalogue',
              style: TextStyle(
                color: Color(0xFF1A1A2E),
                fontSize: 28,
                fontWeight: FontWeight.bold,
              ),
            ),
          ),
          Padding(
            padding: const EdgeInsets.symmetric(
              horizontal: 20,
              vertical: 12,
            ),
            child: TextField(
              controller: _searchController,
              textInputAction: TextInputAction.search,
              onSubmitted: (_) => _loadBooks(),
              decoration: InputDecoration(
                hintText: 'Rechercher un livre...',
                prefixIcon: const Icon(Icons.search),
                suffixIcon: _searchController.text.isNotEmpty
                    ? IconButton(
                        onPressed: () {
                          _searchController.clear();
                          setState(() {});
                          _loadBooks();
                        },
                        icon: const Icon(Icons.clear),
                      )
                    : null,
                filled: true,
                fillColor: Colors.white,
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(6),
                  borderSide: const BorderSide(
                    color: Color(0xFFE5E7EB),
                  ),
                ),
                enabledBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(6),
                  borderSide: const BorderSide(
                    color: Color(0xFFE5E7EB),
                  ),
                ),
                focusedBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(6),
                  borderSide: const BorderSide(
                    color: Color(0xFF2255CC),
                    width: 1.5,
                  ),
                ),
              ),
              onChanged: (_) {
                setState(() {});
              },
            ),
          ),
          _buildCategoryFilter(),
          const SizedBox(height: 4),
          Expanded(
            child: RefreshIndicator(
              onRefresh: _refresh,
              child: _buildContent(),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildCategoryFilter() {
    if (_isLoadingCategories) {
      return const SizedBox(
        height: 46,
        child: Center(
          child: SizedBox(
            width: 20,
            height: 20,
            child: CircularProgressIndicator(
              strokeWidth: 2,
            ),
          ),
        ),
      );
    }

    return SizedBox(
      height: 46,
      child: ListView(
        scrollDirection: Axis.horizontal,
        padding: const EdgeInsets.symmetric(horizontal: 20),
                children: [
          _buildCategoryChip(
            label: 'Toutes',
            categoryName: null,
          ),
          ..._categories.map((category) {
            final name = category['nom']?.toString() ?? '';

            return _buildCategoryChip(
              label: name,
              categoryName: name,
            );
          }),
        ],
      ),
    );
  }

  Widget _buildCategoryChip({
  required String label,
  required String? categoryName,
}) {
  final isSelected = _selectedCategoryName == categoryName;

  return Padding(
    padding: const EdgeInsets.only(right: 8),
    child: ChoiceChip(
      label: Text(label),
      selected: isSelected,
      onSelected: (_) => _selectCategory(categoryName),
      selectedColor: const Color(0xFF2255CC),
      backgroundColor: Colors.white,
      side: const BorderSide(
        color: Color(0xFFE5E7EB),
      ),
      labelStyle: TextStyle(
        color: isSelected
            ? Colors.white
            : const Color(0xFF1A1A2E),
        fontWeight: FontWeight.w600,
        fontSize: 13,
      ),
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(20),
      ),
    ),
  );
}
  Widget _buildContent() {
    if (_isLoading) {
      return const Center(
        child: CircularProgressIndicator(),
      );
    }

    if (_errorMessage != null) {
      return ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        children: [
          SizedBox(
            height: 300,
            child: Center(
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
                      'Impossible de charger le catalogue.',
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
                      onPressed: _loadBooks,
                      icon: const Icon(Icons.refresh),
                      label: const Text('Réessayer'),
                    ),
                  ],
                ),
              ),
            ),
          ),
        ],
      );
    }

    if (_books.isEmpty) {
      return ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        children: const [
          SizedBox(
            height: 300,
            child: Center(
              child: Text(
                'Aucun livre disponible.',
                style: TextStyle(
                  color: Color(0xFF6B7280),
                  fontSize: 16,
                ),
              ),
            ),
          ),
        ],
      );
    }

    return GridView.builder(
      physics: const AlwaysScrollableScrollPhysics(),
      padding: const EdgeInsets.fromLTRB(20, 12, 20, 30),
      itemCount: _books.length,
      gridDelegate:
          const SliverGridDelegateWithFixedCrossAxisCount(
        crossAxisCount: 2,
        crossAxisSpacing: 16,
        mainAxisSpacing: 16,
        childAspectRatio: 0.64,
      ),
      itemBuilder: (context, index) {
        final book = _books[index];

        return BookCard(
          book: book,
          onTap: () {
            Navigator.push(
              context,
              MaterialPageRoute(
                builder: (context) => BookDetailScreen(
                  book: book,
                ),
              ),
            );
          },
        );
      },
    );
  }
}